<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Services\FinancialRatioService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesAccountingCompany;

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isEndUser()) {
            $financialRatios = null;
            if ($user->company_id && $user->memberPortalState() === 'ok') {
                $financialRatios = $this->makeFinancialRatiosPayload(
                    companyId: $user->company_id,
                    canOpenReports: false,
                    forAdmin: false,
                );
            }

            return Inertia::render('Dashboard', [
                'stats' => ['accounts' => 0, 'journal_entries' => 0],
                'readOnly' => true,
                'role' => $user->role,
                'endUserPortal' => [
                    'state' => $user->memberPortalState(),
                    'can_view_finance' => $user->canViewMemberFinancePortal(),
                ],
                'financialRatios' => $financialRatios,
            ]);
        }

        $accountsQuery = ChartAccount::query();
        $entriesQuery = JournalEntry::query();

        if (! $user->isAdmin()) {
            $accountsQuery->forUser($user);
            $entriesQuery->forUser($user);
        }

        $financialRatios = null;
        if ($user->canViewAccountingReports()) {
            $company = $this->optionalAccountingCompany($request);
            if ($company !== null) {
                $financialRatios = $this->makeFinancialRatiosPayload(
                    companyId: $company->id,
                    canOpenReports: true,
                    forAdmin: $user->isAdmin(),
                );
            }
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'accounts' => $accountsQuery->count(),
                'journal_entries' => $entriesQuery->count(),
            ],
            'readOnly' => false,
            'role' => $user->role,
            'endUserPortal' => null,
            'financialRatios' => $financialRatios,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function makeFinancialRatiosPayload(int $companyId, bool $canOpenReports, bool $forAdmin): ?array
    {
        $snap = (new FinancialRatioService($companyId))->forDashboard();

        return array_merge($snap, [
            'can_open_reports' => $canOpenReports,
            'admin_company_id' => $forAdmin ? $companyId : null,
        ]);
    }
}
