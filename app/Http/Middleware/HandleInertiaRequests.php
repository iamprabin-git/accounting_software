<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\Member;
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
        $approvalNotifications = [
            'total' => 0,
            'pending_members' => 0,
            'pending_savings_approvals' => 0,
        ];
        $platformAdminCompanies = null;
        $companyHolidayDates = [];
        $companyWorkingOverrideDates = [];
        $cbsHolidayBlackoutEnabled = false;
        if ($user) {
            $resolvedCompany = Company::resolvedForWebRequest($request);
            $companyFeatures = $resolvedCompany?->featureFlagsForFrontend();

            if ($resolvedCompany) {
                $companyHolidayDates = $resolvedCompany->holidays()
                    ->orderBy('holiday_date')
                    ->pluck('holiday_date')
                    ->map(fn ($d) => $d->toDateString())
                    ->values()
                    ->all();
                $companyWorkingOverrideDates = $resolvedCompany->workingDayOverrides()
                    ->orderBy('work_date')
                    ->pluck('work_date')
                    ->map(fn ($d) => $d->toDateString())
                    ->values()
                    ->all();
                $cbsHolidayBlackoutEnabled = $resolvedCompany->cbsHolidayBlackoutEnabled();
            }

            if ($user->isAdmin()) {
                $platformAdminCompanies = Company::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Company $c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                    ])
                    ->values()
                    ->all();
            }

            if ($resolvedCompany && $user->canEditAccounting()) {
                $pendingMembers = Member::query()
                    ->where('company_id', $resolvedCompany->id)
                    ->where('status', Member::STATUS_PENDING)
                    ->count();

                $pendingSavingsApprovals = FinancialPosition::query()
                    ->where('company_id', $resolvedCompany->id)
                    ->where('category', FinancialPosition::CATEGORY_SAVINGS)
                    ->where('savings_workflow_status', FinancialPosition::LOAN_WORKFLOW_PENDING_APPROVAL)
                    ->count();

                $approvalNotifications = [
                    'total' => $pendingMembers + $pendingSavingsApprovals,
                    'pending_members' => $pendingMembers,
                    'pending_savings_approvals' => $pendingSavingsApprovals,
                ];
            }
        }

        return [
            ...parent::share($request),
            'flash' => [
                'status' => $request->session()->get('status'),
                'error' => $request->session()->get('error'),
                'posted_journal_id' => $request->session()->get('posted_journal_id'),
                'balance_warning' => $request->session()->get('balance_warning'),
            ],
            'company_features' => $companyFeatures,
            'current_company_id' => $resolvedCompany?->id,
            'current_company' => $resolvedCompany?->only('id', 'name'),
            'company_holiday_dates' => $companyHolidayDates,
            'company_working_override_dates' => $companyWorkingOverrideDates,
            'cbs_holiday_blackout_enabled' => $cbsHolidayBlackoutEnabled,
            'platform_admin_companies' => $platformAdminCompanies,
            'approval_notifications' => $approvalNotifications,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'profile_photo_url' => $user->profilePhotoPublicUrl(),
                    'avatar_url' => $user->avatar_url,
                    'avatar_display_url' => $user->avatarDisplayUrl(),
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
                    'can_manage_company_settings' => $user->canManageCompanyWebSettings(),
                    'is_end_user' => $user->isEndUser(),
                ] : null,
            ],
        ];
    }
}
