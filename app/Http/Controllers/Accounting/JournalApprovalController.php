<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JournalApprovalComment;
use App\Models\JournalEntry;
use App\Services\AccountingAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalApprovalController extends Controller
{
    use ResolvesAccountingCompany;

    private function validateAdminCompanySelection(Request $request): void
    {
        if (! $request->user()->isAdmin()) {
            return;
        }

        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $request->session()->put('accounting_company_id', (int) $request->input('company_id'));
    }

    public function approve(Request $request, int $journal): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);
        $validated = $request->validate([
            'approval_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->with('lines')
            ->findOrFail($journal);

        $this->authorize('approve', $journalEntry);

        if (! $journalEntry->isBalanced()) {
            return redirect()
                ->route('journals.show', array_merge(
                    ['journal' => $journalEntry->id],
                    $this->companyQuery($request),
                ))
                ->withErrors([
                    'approve' => __('This entry is not balanced and cannot be approved.'),
                ]);
        }

        if ($company->isJournalDateLocked($journalEntry->transaction_date)) {
            throw ValidationException::withMessages([
                'approve' => __('This period is locked. Approve using an open transaction date.'),
            ]);
        }

        $approvedNow = false;
        $needsSecondApproval = false;

        DB::transaction(function () use ($journalEntry, $request, $company, $validated, &$approvedNow, &$needsSecondApproval) {
            $lockedCompany = Company::query()
                ->whereKey($company->id)
                ->lockForUpdate()
                ->firstOrFail();

            $totalDebitCents = $journalEntry->totalDebitCents();
            $requiresDualApproval = $lockedCompany->dual_approval_threshold_cents !== null
                && $totalDebitCents >= (int) $lockedCompany->dual_approval_threshold_cents;

            $postedNumber = $journalEntry->posted_number;
            if ($requiresDualApproval && $journalEntry->first_approved_by_user_id === null) {
                $journalEntry->update([
                    'first_approved_by_user_id' => $request->user()->id,
                    'first_approved_at' => now(),
                    'status' => JournalEntry::STATUS_PENDING,
                ]);
                $needsSecondApproval = true;

                app(AccountingAuditService::class)->logForJournal(
                    $journalEntry,
                    'journal.first_approved',
                    $request->user(),
                    [
                        'after' => [
                            'first_approved_by_user_id' => $journalEntry->first_approved_by_user_id,
                            'first_approved_at' => $journalEntry->first_approved_at?->toIso8601String(),
                        ],
                    ],
                    $request,
                );
            } else {
                if ($postedNumber === null) {
                    $postedNumber = (int) $lockedCompany->next_journal_posted_number;
                    $lockedCompany->next_journal_posted_number = $postedNumber + 1;
                    $lockedCompany->save();
                }

                $journalEntry->update([
                    'status' => JournalEntry::STATUS_APPROVED,
                    'approved_by_user_id' => $request->user()->id,
                    'approved_at' => now(),
                    'posted_number' => $postedNumber,
                ]);
                $approvedNow = true;

                app(AccountingAuditService::class)->logForJournal(
                    $journalEntry,
                    'journal.approved',
                    $request->user(),
                    ['posted_number' => $postedNumber],
                    $request,
                );
            }

            if (! empty($validated['approval_comment'])) {
                JournalApprovalComment::query()->create([
                    'company_id' => $journalEntry->company_id,
                    'journal_entry_id' => $journalEntry->id,
                    'user_id' => $request->user()->id,
                    'action' => $approvedNow ? 'approve' : 'first_approve',
                    'comment' => $validated['approval_comment'],
                    'created_at' => now(),
                ]);
            }
        });

        if ($needsSecondApproval) {
            return redirect()->route('journals.show', array_merge(
                ['journal' => $journalEntry->id],
                $this->companyQuery($request),
            ))->with('status', __('First approval recorded. A second approver is required for final approval.'));
        }

        return redirect()->route('journals.show', array_merge(
            ['journal' => $journalEntry->id],
            $this->companyQuery($request),
        ))->with('status', __('Journal entry approved. Reports now include this entry.'));
    }

    public function reject(Request $request, int $journal): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);
        $validated = $request->validate([
            'reject_reason' => ['required', 'string', 'max:2000'],
        ]);

        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->findOrFail($journal);

        $this->authorize('reject', $journalEntry);

        $journalEntry->update([
            'status' => JournalEntry::STATUS_DRAFT,
            'submitted_at' => null,
            'first_approved_by_user_id' => null,
            'first_approved_at' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
        ]);

        JournalApprovalComment::query()->create([
            'company_id' => $journalEntry->company_id,
            'journal_entry_id' => $journalEntry->id,
            'user_id' => $request->user()->id,
            'action' => 'reject',
            'comment' => $validated['reject_reason'],
            'created_at' => now(),
        ]);

        app(AccountingAuditService::class)->logForJournal(
            $journalEntry,
            'journal.rejected_to_draft',
            $request->user(),
            ['reason' => $validated['reject_reason']],
            $request,
        );

        return redirect()->route('journals.show', array_merge(
            ['journal' => $journalEntry->id],
            $this->companyQuery($request),
        ))->with('status', __('Entry returned to draft for changes.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function companyQuery(Request $request): array
    {
        if ($request->user()->isAdmin()) {
            return ['company_id' => $this->accountingCompany($request)->id];
        }

        return [];
    }
}
