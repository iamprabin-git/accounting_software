<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing('company');
        }

        $companyFeatures = null;
        $resolvedCompany = null;
        if ($user) {
            $resolvedCompany = Company::resolvedForWebRequest($request);
            $companyFeatures = $resolvedCompany?->featureFlagsForFrontend();
        }

        return [
            ...parent::share($request),
            'flash' => [
                'status' => $request->session()->get('status'),
                'error' => $request->session()->get('error'),
            ],
            'company_features' => $companyFeatures,
            'current_company_id' => $resolvedCompany?->id,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'role' => $user->role,
                    'company_id' => $user->company_id,
                    'company' => $user->company?->only('id', 'name'),
                    'can_manage_team' => $user->canManageTeam(),
                    'can_edit_accounting' => $user->canEditAccounting(),
                    'can_create_journals' => $user->canCreateJournalEntries(),
                    'can_register_members' => $user->isStaff() || $user->isAdmin(),
                    'can_approve_journals' => $user->canApproveJournalEntries(),
                    'can_approve_chart_accounts' => $user->canApproveChartAccounts(),
                    'can_view_reports' => $user->canViewAccountingReports(),
                    'can_manage_chart_of_accounts' => $user->canManageChartOfAccounts(),
                    'is_end_user' => $user->isEndUser(),
                ] : null,
            ],
        ];
    }
}
