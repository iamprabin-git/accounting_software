<?php

namespace App\Services;

use App\Events\JournalEntryPosted;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinanceJournalPostingService
{
    /**
     * Balanced two-line journal. Approved immediately when the user may approve journals; otherwise draft.
     */
    public function postTwoLineJournal(
        int $companyId,
        User $user,
        string $transactionDate,
        string $memo,
        ?string $reference,
        int $amountCents,
        int $debitChartAccountId,
        int $creditChartAccountId,
        ?int $memberId = null,
        ?string $financeCategory = null,
    ): JournalEntry {
        if ($amountCents <= 0) {
            throw new InvalidArgumentException(__('Amount must be positive.'));
        }

        $this->assertApprovedChartAccount($companyId, $debitChartAccountId);
        $this->assertApprovedChartAccount($companyId, $creditChartAccountId);

        // Product movement postings (loan/savings/investment) must stay in sync with
        // principal updates, so they are auto-approved journal entries.
        $approved = $financeCategory !== null
            ? true
            : $user->canApproveJournalEntries();

        return DB::transaction(function () use (
            $companyId,
            $user,
            $transactionDate,
            $memo,
            $reference,
            $amountCents,
            $debitChartAccountId,
            $creditChartAccountId,
            $approved,
            $memberId,
            $financeCategory,
        ) {
            $lockedCompany = Company::query()
                ->whereKey($companyId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($approved && $lockedCompany->isJournalDateLocked($transactionDate)) {
                throw new InvalidArgumentException(__('This period is locked. Post using an open transaction date.'));
            }

            if (! Company::isWorkingTransactionDate($companyId, $transactionDate)) {
                throw new InvalidArgumentException(
                    __('This date is not a working day. Use a weekday, remove the holiday, or add a weekend override on the company calendar.'),
                );
            }

            $postedNumber = null;
            if ($approved) {
                $postedNumber = (int) $lockedCompany->next_journal_posted_number;
                $lockedCompany->next_journal_posted_number = $postedNumber + 1;
                $lockedCompany->save();
            }

            $entry = JournalEntry::query()->create([
                'company_id' => $companyId,
                'member_id' => $memberId,
                'finance_category' => $financeCategory,
                'user_id' => $user->id,
                'reference' => $reference,
                'memo' => $memo,
                'transaction_date' => $transactionDate,
                'status' => $approved ? JournalEntry::STATUS_APPROVED : JournalEntry::STATUS_DRAFT,
                'approved_by_user_id' => $approved ? $user->id : null,
                'approved_at' => $approved ? now() : null,
                'posted_number' => $postedNumber,
            ]);

            $entry->lines()->create([
                'chart_account_id' => $debitChartAccountId,
                'debit_cents' => $amountCents,
                'credit_cents' => 0,
                'description' => null,
            ]);
            $entry->lines()->create([
                'chart_account_id' => $creditChartAccountId,
                'debit_cents' => 0,
                'credit_cents' => $amountCents,
                'description' => null,
            ]);

            app(AccountingAuditService::class)->logForJournal(
                $entry,
                $approved ? 'journal.auto_posted' : 'journal.auto_created_draft',
                $user,
                [
                    'finance_category' => $financeCategory,
                    'amount_cents' => $amountCents,
                ],
            );

            $fresh = $entry->fresh(['lines']);

            if ($approved && $fresh !== null) {
                JournalEntryPosted::dispatch($fresh);
            }

            return $fresh;
        });
    }

    /**
     * Multi-line balanced journal (e.g. savings interest with tax withheld to a liability).
     *
     * @param  list<array{chart_account_id: int, debit_cents: int, credit_cents: int, description?: string|null}>  $lines
     */
    public function postBalancedLinesJournal(
        int $companyId,
        User $user,
        string $transactionDate,
        string $memo,
        ?string $reference,
        array $lines,
        ?int $memberId = null,
        ?string $financeCategory = null,
    ): JournalEntry {
        if ($lines === []) {
            throw new InvalidArgumentException(__('At least one journal line is required.'));
        }

        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($lines as $line) {
            $d = (int) ($line['debit_cents'] ?? 0);
            $c = (int) ($line['credit_cents'] ?? 0);
            if (($d > 0 && $c > 0) || ($d === 0 && $c === 0)) {
                throw new InvalidArgumentException(__('Each line must have either a debit or a credit, not both.'));
            }
            if ($d < 0 || $c < 0) {
                throw new InvalidArgumentException(__('Amounts must not be negative.'));
            }
            $totalDebit += $d;
            $totalCredit += $c;
            $this->assertApprovedChartAccount($companyId, (int) $line['chart_account_id']);
        }

        if ($totalDebit !== $totalCredit || $totalDebit <= 0) {
            throw new InvalidArgumentException(__('Journal lines must balance with positive totals.'));
        }

        $approved = $financeCategory !== null
            ? true
            : $user->canApproveJournalEntries();

        return DB::transaction(function () use (
            $companyId,
            $user,
            $transactionDate,
            $memo,
            $reference,
            $lines,
            $approved,
            $memberId,
            $financeCategory,
            $totalDebit,
        ) {
            $lockedCompany = Company::query()
                ->whereKey($companyId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($approved && $lockedCompany->isJournalDateLocked($transactionDate)) {
                throw new InvalidArgumentException(__('This period is locked. Post using an open transaction date.'));
            }

            if (! Company::isWorkingTransactionDate($companyId, $transactionDate)) {
                throw new InvalidArgumentException(
                    __('This date is not a working day. Use a weekday, remove the holiday, or add a weekend override on the company calendar.'),
                );
            }

            $postedNumber = null;
            if ($approved) {
                $postedNumber = (int) $lockedCompany->next_journal_posted_number;
                $lockedCompany->next_journal_posted_number = $postedNumber + 1;
                $lockedCompany->save();
            }

            $entry = JournalEntry::query()->create([
                'company_id' => $companyId,
                'member_id' => $memberId,
                'finance_category' => $financeCategory,
                'user_id' => $user->id,
                'reference' => $reference,
                'memo' => $memo,
                'transaction_date' => $transactionDate,
                'status' => $approved ? JournalEntry::STATUS_APPROVED : JournalEntry::STATUS_DRAFT,
                'approved_by_user_id' => $approved ? $user->id : null,
                'approved_at' => $approved ? now() : null,
                'posted_number' => $postedNumber,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'chart_account_id' => (int) $line['chart_account_id'],
                    'debit_cents' => (int) ($line['debit_cents'] ?? 0),
                    'credit_cents' => (int) ($line['credit_cents'] ?? 0),
                    'description' => $line['description'] ?? null,
                ]);
            }

            app(AccountingAuditService::class)->logForJournal(
                $entry,
                $approved ? 'journal.auto_posted' : 'journal.auto_created_draft',
                $user,
                [
                    'finance_category' => $financeCategory,
                    'amount_cents' => $totalDebit,
                    'line_count' => count($lines),
                ],
            );

            $fresh = $entry->fresh(['lines']);

            if ($approved && $fresh !== null) {
                JournalEntryPosted::dispatch($fresh);
            }

            return $fresh;
        });
    }

    private function assertApprovedChartAccount(int $companyId, int $chartAccountId): void
    {
        ChartAccount::query()
            ->where('company_id', $companyId)
            ->whereKey($chartAccountId)
            ->where('approval_status', ChartAccount::STATUS_APPROVED)
            ->firstOrFail();
    }
}
