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

    private function assertApprovedChartAccount(int $companyId, int $chartAccountId): void
    {
        ChartAccount::query()
            ->where('company_id', $companyId)
            ->whereKey($chartAccountId)
            ->where('approval_status', ChartAccount::STATUS_APPROVED)
            ->firstOrFail();
    }
}
