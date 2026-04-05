<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\ChartAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChartAccountApprovalController extends Controller
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

    public function approve(Request $request, int $account): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $chartAccount = ChartAccount::query()
            ->where('company_id', $company->id)
            ->findOrFail($account);

        $this->authorize('approve', $chartAccount);

        $chartAccount->update([
            'approval_status' => ChartAccount::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $request->user()->id,
        ]);

        return redirect()->route('chart-accounts.index', $this->companyQuery($request))
            ->with('status', __('Chart account approved. It can be used in journal entries.'));
    }

    public function reject(Request $request, int $account): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $chartAccount = ChartAccount::query()
            ->where('company_id', $company->id)
            ->findOrFail($account);

        $this->authorize('reject', $chartAccount);

        if ($chartAccount->journalLines()->exists()) {
            return redirect()->route('chart-accounts.index', $this->companyQuery($request))
                ->withErrors([
                    'reject' => __('This account cannot be removed because it is already used on journal lines.'),
                ]);
        }

        $chartAccount->delete();

        return redirect()->route('chart-accounts.index', $this->companyQuery($request))
            ->with('status', __('Proposed chart account was declined and removed.'));
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
