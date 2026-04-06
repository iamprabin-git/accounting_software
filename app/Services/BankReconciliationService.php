<?php

namespace App\Services;

use App\Models\BankReconciliationBatch;
use App\Models\BankStatementLine;
use App\Models\BankStatementLineMatch;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankReconciliationService
{
    /**
     * @return list<array{transaction_date: string, amount_cents: int, description: ?string, external_reference: ?string}>
     */
    public function parseCsv(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            throw new InvalidArgumentException(__('CSV content is empty.'));
        }

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $lines = array_values(array_filter($lines, fn ($l) => trim((string) $l) !== ''));

        if ($lines === []) {
            throw new InvalidArgumentException(__('CSV has no rows.'));
        }

        $headerLine = array_shift($lines);
        $delimiter = $this->detectDelimiter($headerLine);
        $headerRow = str_getcsv($headerLine, $delimiter);
        $headerRow = array_map(fn ($h) => strtolower(trim((string) $h, " \t\"\xEF\xBB\xBF")), $headerRow);

        $dateIdx = $this->findColumn($headerRow, [
            'date', 'txn_date', 'transaction_date', 'posting_date', 'value_date', 'book_date',
        ]);

        $amountIdx = $this->findColumn($headerRow, ['amount', 'amt', 'value', 'net', 'net_amount']);

        $debitIdx = $this->findColumn($headerRow, [
            'debit', 'dr', 'withdrawal', 'withdrawals', 'out', 'payment', 'paid_out',
        ]);
        $creditIdx = $this->findColumn($headerRow, [
            'credit', 'cr', 'deposit', 'deposits', 'in', 'received', 'lodgement',
        ]);

        if ($dateIdx === null) {
            throw new InvalidArgumentException(__('CSV must include a date column (e.g. date).'));
        }

        if ($amountIdx === null && ($debitIdx === null || $creditIdx === null)) {
            throw new InvalidArgumentException(
                __('CSV must include either an amount column or both debit and credit columns.'),
            );
        }

        $descIdx = $this->findColumn($headerRow, [
            'description', 'narration', 'memo', 'details', 'particulars', 'payee', 'merchant', 'name',
        ]);
        $refIdx = $this->findColumn($headerRow, [
            'reference', 'ref', 'txn_id', 'transaction_id', 'cheque', 'check', 'fit_id', 'bank_reference',
        ]);

        $out = [];
        foreach ($lines as $i => $line) {
            $cols = str_getcsv($line, $delimiter);
            if (count(array_filter($cols, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $dateRaw = trim((string) ($cols[$dateIdx] ?? ''));
            if ($dateRaw === '') {
                continue;
            }

            try {
                $date = Carbon::parse($dateRaw)->toDateString();
            } catch (\Throwable) {
                throw new InvalidArgumentException(__('Invalid date on row :row.', ['row' => $i + 2]));
            }

            if ($amountIdx !== null) {
                $amountRaw = trim((string) ($cols[$amountIdx] ?? ''));
                if ($amountRaw === '') {
                    continue;
                }
                $amountCents = $this->parseAmountToCents($amountRaw);
            } else {
                $dRaw = trim((string) ($cols[$debitIdx] ?? ''));
                $cRaw = trim((string) ($cols[$creditIdx] ?? ''));
                if ($dRaw === '' && $cRaw === '') {
                    continue;
                }
                $debitC = $dRaw === '' ? 0 : abs($this->parseAmountToCents($dRaw));
                $creditC = $cRaw === '' ? 0 : abs($this->parseAmountToCents($cRaw));
                $amountCents = $creditC - $debitC;
            }

            $out[] = [
                'transaction_date' => $date,
                'amount_cents' => $amountCents,
                'description' => $descIdx !== null ? $this->truncate(trim((string) ($cols[$descIdx] ?? '')), 500) : null,
                'external_reference' => $refIdx !== null ? $this->truncate(trim((string) ($cols[$refIdx] ?? '')), 120) : null,
            ];
        }

        if ($out === []) {
            throw new InvalidArgumentException(__('No data rows found after the header.'));
        }

        return $out;
    }

    public function detectDelimiter(string $firstLine): string
    {
        $tabCount = substr_count($firstLine, "\t");
        $commaCount = substr_count($firstLine, ',');

        return $tabCount > $commaCount ? "\t" : ',';
    }

    /**
     * Optional opening/closing from user input (decimal string). Null if blank.
     */
    public function parseOptionalBalanceCents(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        return $this->parseAmountToCents($raw);
    }

    /**
     * @param  list<array{amount_cents: int}>  $rows
     */
    public function balanceCheckVariance(
        ?int $openingCents,
        ?int $closingCents,
        array $rows,
    ): ?int {
        if ($openingCents === null || $closingCents === null) {
            return null;
        }

        $sum = 0;
        foreach ($rows as $r) {
            $sum += (int) $r['amount_cents'];
        }
        $expectedClosing = $openingCents + $sum;

        return $expectedClosing - $closingCents;
    }

    public function matchedNetSum(BankStatementLine $stmt): int
    {
        $row = DB::selectOne(
            'SELECT COALESCE(SUM(jl.debit_cents - jl.credit_cents), 0) AS n
             FROM bank_statement_line_matches m
             INNER JOIN journal_lines jl ON jl.id = m.journal_line_id
             WHERE m.bank_statement_line_id = ?',
            [$stmt->id],
        );

        return (int) ($row->n ?? 0);
    }

    public function syncReconciledFlag(BankStatementLine $stmt): void
    {
        $stmt->refresh();
        $sum = $this->matchedNetSum($stmt);
        if ($sum === (int) $stmt->amount_cents && $stmt->matches()->exists()) {
            if ($stmt->reconciled_at === null) {
                $stmt->reconciled_at = now();
                $stmt->save();
            }
        } else {
            $stmt->reconciled_at = null;
            $stmt->save();
        }
    }

    /**
     * @param  list<string>  $header
     */
    protected function findColumn(array $header, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $idx = array_search($alias, $header, true);
            if ($idx !== false) {
                return (int) $idx;
            }
        }

        return null;
    }

    protected function parseAmountToCents(string $raw): int
    {
        $raw = str_replace([',', ' '], '', $raw);
        $negative = false;
        if (str_starts_with($raw, '(') && str_ends_with($raw, ')')) {
            $negative = true;
            $raw = substr($raw, 1, -1);
        }
        if (str_starts_with($raw, '-')) {
            $negative = true;
            $raw = ltrim($raw, '-');
        }

        if ($raw === '' || ! is_numeric($raw)) {
            throw new InvalidArgumentException(__('Invalid amount: :v', ['v' => $raw]));
        }

        $cents = (int) round((float) $raw * 100);
        if ($negative) {
            $cents = -$cents;
        }

        return $cents;
    }

    protected function truncate(string $s, int $max): ?string
    {
        if ($s === '') {
            return null;
        }

        return mb_substr($s, 0, $max);
    }

    /**
     * @return Collection<int, JournalLine>
     */
    public function unmatchedJournalLinesForAccount(int $companyId, int $chartAccountId): Collection
    {
        return JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->with(['journalEntry'])
            ->where('journal_lines.chart_account_id', $chartAccountId)
            ->where('journal_entries.company_id', $companyId)
            ->where('journal_entries.status', JournalEntry::STATUS_APPROVED)
            ->whereDoesntHave('bankStatementLineMatch')
            ->orderByDesc('journal_entries.transaction_date')
            ->orderByDesc('journal_lines.id')
            ->select('journal_lines.*')
            ->limit(750)
            ->get();
    }

    /**
     * @return list<array{id: int, journal_entry_id: int, transaction_date: string, net_cents: int, description: ?string, reference: ?string}>
     */
    public function suggestJournalLines(
        BankStatementLine $stmt,
        Collection $candidates,
        int $dateWindowDays = 7,
    ): array {
        $stmtDate = $stmt->transaction_date;
        $target = (int) $stmt->amount_cents;
        $matchedSum = $this->matchedNetSum($stmt);
        $remaining = $target - $matchedSum;
        if ($remaining === 0) {
            return [];
        }

        $ref = $stmt->external_reference ? strtolower($stmt->external_reference) : null;

        $scored = [];
        foreach ($candidates as $line) {
            /** @var JournalLine $line */
            $net = $line->netAmountCentsForBankAccount();
            if (! $this->partialAmountAcceptable($remaining, $net)) {
                continue;
            }
            $entry = $line->journalEntry;
            if (! $entry) {
                continue;
            }
            $days = abs($stmtDate->diffInDays($entry->transaction_date));
            if ($days > $dateWindowDays) {
                continue;
            }
            $score = 100 - min(99, (int) $days * 10);
            if ($net === $remaining) {
                $score += 200;
            }
            if ($ref && $entry->reference && str_contains(strtolower($entry->reference), $ref)) {
                $score += 50;
            }
            $scored[] = [
                'score' => $score,
                'line' => $line,
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_values(array_map(function (array $row) {
            /** @var JournalLine $line */
            $line = $row['line'];
            $entry = $line->journalEntry;

            return [
                'id' => $line->id,
                'journal_entry_id' => (int) $line->journal_entry_id,
                'transaction_date' => $entry->transaction_date->toDateString(),
                'net_cents' => $line->netAmountCentsForBankAccount(),
                'description' => $line->description ?: $entry->memo,
                'reference' => $entry->reference,
            ];
        }, array_slice($scored, 0, 8)));
    }

    /**
     * Whether adding this journal net toward a statement line with remaining R is allowed (no overshoot).
     */
    public function partialAmountAcceptable(int $remaining, int $journalNet): bool
    {
        if ($remaining === 0) {
            return false;
        }
        if ($remaining > 0) {
            return $journalNet > 0 && $journalNet <= $remaining;
        }

        return $journalNet < 0 && $journalNet >= $remaining;
    }

    public function autoMatchBatch(BankReconciliationBatch $batch): int
    {
        $candidates = $this->unmatchedJournalLinesForAccount(
            (int) $batch->company_id,
            (int) $batch->chart_account_id,
        );

        $matched = 0;
        foreach ($batch->statementLines()->whereNull('reconciled_at')->orderBy('id')->get() as $stmt) {
            if ($this->matchedNetSum($stmt) !== 0) {
                continue;
            }
            $hits = [];
            foreach ($candidates as $line) {
                if ($line->netAmountCentsForBankAccount() !== (int) $stmt->amount_cents) {
                    continue;
                }
                $entry = $line->journalEntry;
                if (! $entry) {
                    continue;
                }
                if (abs($stmt->transaction_date->diffInDays($entry->transaction_date)) > 3) {
                    continue;
                }
                $hits[] = $line;
            }
            if (count($hits) === 1) {
                $this->addMatch($stmt, $hits[0]);
                $candidates = $candidates->reject(fn (JournalLine $l) => $l->id === $hits[0]->id)->values();
                $matched++;
            }
        }

        return $matched;
    }

    public function addMatch(BankStatementLine $stmt, JournalLine $line): void
    {
        $stmt->loadMissing('batch');

        if ($line->bankStatementLineMatch !== null) {
            throw new InvalidArgumentException(__('Journal line is already reconciled.'));
        }
        if ((int) $line->chart_account_id !== (int) $stmt->batch->chart_account_id) {
            throw new InvalidArgumentException(__('Journal line is not on the selected bank account.'));
        }
        $entry = $line->journalEntry;
        if (! $entry || $entry->status !== JournalEntry::STATUS_APPROVED) {
            throw new InvalidArgumentException(__('Only approved journal lines can be reconciled.'));
        }
        if ((int) $entry->company_id !== (int) $stmt->batch->company_id) {
            throw new InvalidArgumentException(__('Journal entry does not belong to this company.'));
        }

        $target = (int) $stmt->amount_cents;
        $matchedSum = $this->matchedNetSum($stmt);
        $remaining = $target - $matchedSum;
        $net = $line->netAmountCentsForBankAccount();

        if (! $this->partialAmountAcceptable($remaining, $net)) {
            throw new InvalidArgumentException(
                __('This journal line does not fit the remaining statement amount (:remaining).', [
                    'remaining' => number_format($remaining / 100, 2),
                ]),
            );
        }

        BankStatementLineMatch::query()->create([
            'bank_statement_line_id' => $stmt->id,
            'journal_line_id' => $line->id,
        ]);

        $this->syncReconciledFlag($stmt);
    }

    public function removeMatch(BankStatementLineMatch $match): void
    {
        $stmt = $match->bankStatementLine;
        $match->delete();
        if ($stmt) {
            $this->syncReconciledFlag($stmt);
        }
    }

    public function clearAllMatches(BankStatementLine $stmt): void
    {
        BankStatementLineMatch::query()->where('bank_statement_line_id', $stmt->id)->delete();
        $stmt->reconciled_at = null;
        $stmt->save();
    }

    public function detachMatch(BankStatementLine $stmt): void
    {
        $this->clearAllMatches($stmt);
    }

    public function assertChartAccountForCompany(ChartAccount $account, int $companyId): void
    {
        if ((int) $account->company_id !== $companyId) {
            throw new InvalidArgumentException(__('Invalid bank account for this company.'));
        }
        if (! $account->isApproved()) {
            throw new InvalidArgumentException(__('Only approved chart accounts can be reconciled.'));
        }
    }
}
