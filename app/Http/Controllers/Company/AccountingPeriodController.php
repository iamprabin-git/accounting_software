<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Services\AccountingAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountingPeriodController extends Controller
{
    public function close(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isCompany() && $user->company_id, 403);

        if (! $user->canApproveJournalEntries()) {
            abort(403);
        }

        $company = Company::query()->findOrFail($user->company_id);
        $before = [
            'journal_lock_date' => $company->journal_lock_date?->toDateString(),
            'journal_lock_reason' => $company->journal_lock_reason,
        ];

        $validated = $request->validate([
            'close_lock_date' => ['required', 'date'],
            'close_reason' => ['required', 'string', 'max:2000'],
        ]);

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

        $company->update([
            'journal_lock_date' => $validated['close_lock_date'],
            'journal_lock_reason' => $validated['close_reason'],
            'journal_lock_updated_by_user_id' => $user->id,
            'journal_lock_updated_at' => now(),
        ]);

        app(AccountingAuditService::class)->logJournalAction(
            $company->id,
            null,
            'period.closed',
            $user,
            [
                'lock_date' => $validated['close_lock_date'],
                'reason' => $validated['close_reason'],
                'before' => $before,
                'after' => [
                    'journal_lock_date' => $company->journal_lock_date?->toDateString(),
                    'journal_lock_reason' => $company->journal_lock_reason,
                ],
            ],
            $request,
        );

        return back()->with('status', __('Accounting period lock updated.'));
    }

    public function reopen(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isCompany() && $user->company_id, 403);

        if (! $user->canApproveJournalEntries()) {
            abort(403);
        }

        $company = Company::query()->findOrFail($user->company_id);
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
}
