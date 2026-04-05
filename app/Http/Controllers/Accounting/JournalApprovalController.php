<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $journalEntry->update([
            'status' => JournalEntry::STATUS_APPROVED,
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return redirect()->route('journals.show', array_merge(
            ['journal' => $journalEntry->id],
            $this->companyQuery($request),
        ))->with('status', __('Journal entry approved. Reports now include this entry.'));
    }

    public function reject(Request $request, int $journal): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $journalEntry = JournalEntry::query()
            ->forCompany($company->id)
            ->findOrFail($journal);

        $this->authorize('reject', $journalEntry);

        $journalEntry->update([
            'status' => JournalEntry::STATUS_DRAFT,
            'submitted_at' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
        ]);

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
