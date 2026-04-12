<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\ResolvesCompanyForCompanyWebRoutes;
use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Services\AccountingAuditService;
use App\Services\NepaliPeriodCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPeriodController extends Controller
{
    use ResolvesCompanyForCompanyWebRoutes;

    public function __construct(
        private readonly NepaliPeriodCalendar $nepaliPeriodCalendar,
    ) {}

    public function close(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && ($user->isCompany() || $user->isAdmin()), 403);

        if (! $user->canApproveJournalEntries() && ! $user->isAdmin()) {
            abort(403);
        }

        $company = $this->companyForCompanyWebRoutes($request);

        $validated = $request->validate([
            'close_lock_date' => ['required', 'date'],
            'close_reason' => ['required', 'string', 'max:2000'],
            'close_type' => ['nullable', 'in:custom,month_end,quarter_end,year_end,fiscal_year_end'],
            'retained_earnings_account_id' => ['nullable', 'integer'],
        ]);

        $closeType = $validated['close_type'] ?? 'custom';
        $closeDate = Carbon::parse($validated['close_lock_date'])->startOfDay();

        if (in_array($closeType, ['month_end', 'quarter_end', 'year_end', 'fiscal_year_end'], true)
            && $this->nepaliPeriodCalendar->adToBs($closeDate) === null) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('Nepali calendar conversion is not available for this date. Choose another date or use Custom close type.'),
            ]);
        }

        if ($closeType === 'month_end' && ! $this->nepaliPeriodCalendar->isLastDayOfNepaliMonth($closeDate)) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('For month-end close, the date must be the last day of the month in the Bikram Sambat (Nepali) calendar.'),
            ]);
        }
        if ($closeType === 'quarter_end' && ! $this->nepaliPeriodCalendar->isNepaliFiscalQuarterEnd($closeDate)) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('For quarter-end close, the date must be the last day of a Nepali fiscal quarter (end of Ashwin, Paush, Chaitra, or Ashadh).'),
            ]);
        }
        if ($closeType === 'year_end' && ! $this->nepaliPeriodCalendar->isNepaliCalendarYearEnd($closeDate)) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('For year-end close, the date must be the last day of Chaitra (Bikram Sambat calendar year end).'),
            ]);
        }

        if (
            $company->journal_lock_date !== null
            && $validated['close_lock_date'] < $company->journal_lock_date->toDateString()
        ) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('Close date cannot move backward. Use reopen to reduce the lock date.'),
            ]);
        }

        $draftCount = JournalEntry::query()
            ->where('company_id', $company->id)
            ->where('status', JournalEntry::STATUS_DRAFT)
            ->whereDate('transaction_date', '<=', $validated['close_lock_date'])
            ->count();

        $pendingCount = JournalEntry::query()
            ->where('company_id', $company->id)
            ->where('status', JournalEntry::STATUS_PENDING)
            ->whereDate('transaction_date', '<=', $validated['close_lock_date'])
            ->count();

        $rejectedCount = JournalEntry::query()
            ->where('company_id', $company->id)
            ->where('status', JournalEntry::STATUS_REJECTED)
            ->whereDate('transaction_date', '<=', $validated['close_lock_date'])
            ->count();

        if ($draftCount > 0 || $pendingCount > 0 || $rejectedCount > 0) {
            $parts = [];
            if ($draftCount > 0) {
                $parts[] = __(':count draft journal(s)', ['count' => $draftCount]);
            }
            if ($pendingCount > 0) {
                $parts[] = __(':count pending approval journal(s)', ['count' => $pendingCount]);
            }
            if ($rejectedCount > 0) {
                $parts[] = __(':count rejected journal(s)', ['count' => $rejectedCount]);
            }

            throw ValidationException::withMessages([
                'close_checklist' => __('Period-close checklist failed for this date. Resolve :items dated on or before :date before closing.', [
                    'items' => implode(', ', $parts),
                    'date' => $validated['close_lock_date'],
                ]),
            ]);
        }

        DB::transaction(function () use ($company, $validated, $user, $request, $closeType, $closeDate): void {
            $company->update([
                'journal_lock_date' => $validated['close_lock_date'],
                'journal_lock_reason' => $validated['close_reason'],
                'journal_lock_updated_by_user_id' => $user->id,
                'journal_lock_updated_at' => now(),
                'last_period_close_type' => $closeType,
            ]);

            if ($closeType === 'fiscal_year_end') {
                $this->postFiscalYearClosingEntry(
                    company: $company->fresh(),
                    userId: (int) $user->id,
                    closeDate: $closeDate,
                    retainedEarningsAccountId: isset($validated['retained_earnings_account_id'])
                        ? (int) $validated['retained_earnings_account_id']
                        : null,
                );
            }

            app(AccountingAuditService::class)->logJournalAction(
                $company->id,
                null,
                $closeType === 'fiscal_year_end' ? 'period.closed_fiscal_year' : 'period.closed',
                $user,
                [
                    'lock_date' => $validated['close_lock_date'],
                    'reason' => $validated['close_reason'],
                    'close_type' => $closeType,
                    'before' => [
                        'journal_lock_date' => $company->getOriginal('journal_lock_date'),
                        'journal_lock_reason' => $company->getOriginal('journal_lock_reason'),
                    ],
                    'after' => [
                        'journal_lock_date' => $company->journal_lock_date?->toDateString(),
                        'journal_lock_reason' => $company->journal_lock_reason,
                    ],
                ],
                $request,
            );
        });

        return back()->with('status', __('Accounting period lock updated.'));
    }

    public function reopen(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null && ($user->isCompany() || $user->isAdmin()), 403);

        if (! $user->canApproveJournalEntries() && ! $user->isAdmin()) {
            abort(403);
        }

        $company = $this->companyForCompanyWebRoutes($request);
        $before = [
            'journal_lock_date' => $company->journal_lock_date?->toDateString(),
            'journal_lock_reason' => $company->journal_lock_reason,
        ];

        $validated = $request->validate([
            'reopen_to_date' => ['nullable', 'date'],
            'reopen_reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($company->journal_lock_date === null) {
            throw ValidationException::withMessages([
                'reopen_to_date' => __('No period lock is currently set.'),
            ]);
        }

        $reopenToDate = $validated['reopen_to_date'] ?? null;
        if ($reopenToDate !== null && $reopenToDate > $company->journal_lock_date->toDateString()) {
            throw ValidationException::withMessages([
                'reopen_to_date' => __('Reopen date must be earlier than or equal to the current lock date.'),
            ]);
        }

        $company->update([
            'journal_lock_date' => $reopenToDate,
            'journal_lock_reason' => $validated['reopen_reason'],
            'journal_lock_updated_by_user_id' => $user->id,
            'journal_lock_updated_at' => now(),
        ]);

        app(AccountingAuditService::class)->logJournalAction(
            $company->id,
            null,
            'period.reopened',
            $user,
            [
                'reopen_to_date' => $reopenToDate,
                'reason' => $validated['reopen_reason'],
                'before' => $before,
                'after' => [
                    'journal_lock_date' => $company->journal_lock_date?->toDateString(),
                    'journal_lock_reason' => $company->journal_lock_reason,
                ],
            ],
            $request,
        );

        return back()->with('status', __('Accounting period reopened.'));
    }

    private function postFiscalYearClosingEntry(
        Company $company,
        int $userId,
        Carbon $closeDate,
        ?int $retainedEarningsAccountId
    ): void {
        if (! $this->nepaliPeriodCalendar->isNepaliFiscalYearEnd($closeDate)) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('Fiscal year end close must fall on the last day of Ashadh (end of the Nepal fiscal year in Bikram Sambat).'),
            ]);
        }

        $fyReference = $this->nepaliPeriodCalendar->fiscalCloseReferenceKey($closeDate);
        if ($fyReference === null) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('Unable to determine fiscal year reference for this close date.'),
            ]);
        }

        $existing = JournalEntry::query()
            ->where('company_id', $company->id)
            ->where('reference', $fyReference)
            ->exists();
        if ($existing) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('Fiscal year closing entry already exists for this Nepali fiscal year (duplicate reference).'),
            ]);
        }

        $reAccount = $retainedEarningsAccountId
            ? ChartAccount::query()
                ->where('company_id', $company->id)
                ->where('approval_status', ChartAccount::STATUS_APPROVED)
                ->where('type', ChartAccount::TYPE_EQUITY)
                ->whereKey($retainedEarningsAccountId)
                ->first()
            : ChartAccount::query()
                ->where('company_id', $company->id)
                ->where('approval_status', ChartAccount::STATUS_APPROVED)
                ->where('type', ChartAccount::TYPE_EQUITY)
                ->whereRaw('LOWER(name) LIKE ?', ['%retained%'])
                ->first();

        if (! $reAccount) {
            throw ValidationException::withMessages([
                'retained_earnings_account_id' => __('Select an approved equity account for retained earnings before fiscal year close.'),
            ]);
        }

        $fyStart = $this->nepaliPeriodCalendar->nepaliFiscalYearStartAdForAshadhClose($closeDate);
        if ($fyStart === null) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('Unable to determine the start of the Nepali fiscal year for this close date.'),
            ]);
        }

        $balances = JournalLine::query()
            ->selectRaw('chart_accounts.id as account_id, chart_accounts.type, SUM(journal_lines.debit_cents) as debit_sum, SUM(journal_lines.credit_cents) as credit_sum')
            ->join('chart_accounts', 'chart_accounts.id', '=', 'journal_lines.chart_account_id')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('chart_accounts.company_id', $company->id)
            ->where('journal_entries.company_id', $company->id)
            ->where('journal_entries.status', JournalEntry::STATUS_APPROVED)
            ->whereDate('journal_entries.transaction_date', '>=', $fyStart->toDateString())
            ->whereDate('journal_entries.transaction_date', '<=', $closeDate->toDateString())
            ->whereIn('chart_accounts.type', [ChartAccount::TYPE_REVENUE, ChartAccount::TYPE_EXPENSE])
            ->groupBy('chart_accounts.id', 'chart_accounts.type')
            ->get();

        $lines = [];
        $netToRetained = 0;

        foreach ($balances as $row) {
            $accountId = (int) $row->account_id;
            $debit = (int) $row->debit_sum;
            $credit = (int) $row->credit_sum;
            $type = (string) $row->type;

            if ($type === ChartAccount::TYPE_REVENUE) {
                $net = $credit - $debit;
                if ($net === 0) {
                    continue;
                }
                $lines[] = [
                    'chart_account_id' => $accountId,
                    'debit_cents' => $net > 0 ? $net : 0,
                    'credit_cents' => $net < 0 ? -$net : 0,
                    'description' => 'FY close - revenue reset',
                ];
                $netToRetained += $net;

                continue;
            }

            $net = $debit - $credit;
            if ($net === 0) {
                continue;
            }
            $lines[] = [
                'chart_account_id' => $accountId,
                'debit_cents' => $net < 0 ? -$net : 0,
                'credit_cents' => $net > 0 ? $net : 0,
                'description' => 'FY close - expense reset',
            ];
            $netToRetained -= $net;
        }

        if (empty($lines)) {
            return;
        }

        $reLine = [
            'chart_account_id' => (int) $reAccount->id,
            'debit_cents' => $netToRetained < 0 ? -$netToRetained : 0,
            'credit_cents' => $netToRetained > 0 ? $netToRetained : 0,
            'description' => 'FY close - transfer to retained earnings',
        ];
        $lines[] = $reLine;

        $totalDebit = array_sum(array_map(fn (array $l): int => (int) $l['debit_cents'], $lines));
        $totalCredit = array_sum(array_map(fn (array $l): int => (int) $l['credit_cents'], $lines));
        if ($totalDebit !== $totalCredit || $totalDebit <= 0) {
            throw ValidationException::withMessages([
                'close_lock_date' => __('Unable to post balanced fiscal year close entry.'),
            ]);
        }

        $locked = Company::query()->lockForUpdate()->findOrFail($company->id);
        $postedNumber = (int) $locked->next_journal_posted_number;
        $locked->next_journal_posted_number = $postedNumber + 1;
        $locked->save();

        $entry = JournalEntry::query()->create([
            'company_id' => $company->id,
            'user_id' => $userId,
            'reference' => $fyReference,
            'memo' => 'Nepali fiscal year closing entry (P&L to retained earnings)',
            'transaction_date' => $closeDate->toDateString(),
            'status' => JournalEntry::STATUS_APPROVED,
            'submitted_at' => now(),
            'approved_at' => now(),
            'approved_by_user_id' => $userId,
            'first_approved_by_user_id' => $userId,
            'first_approved_at' => now(),
            'posted_number' => $postedNumber,
        ]);

        foreach ($lines as $line) {
            $entry->lines()->create($line);
        }
    }
}
