<?php

namespace App\Http\Controllers\Banking;

use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\FinancialPosition;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\MemberGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoreBankingController extends Controller
{
    use ResolvesAccountingCompany;

    /**
     * Professional core-banking operations dashboard (members + deposits + loans + GL).
     */
    public function operations(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && $user->canEditAccounting(), 403);

        $company = $this->accountingCompany($request);

        if (! $company->allowsFinanceSuite() || ! $company->allowsMembersModule()) {
            abort(403, __('Core banking hub requires Enterprise with members and finance enabled.'));
        }

        $companyId = $company->id;

        $membersApproved = Member::query()
            ->where('company_id', $companyId)
            ->where('status', Member::STATUS_APPROVED)
            ->count();

        $loanBase = FinancialPosition::query()
            ->where('company_id', $companyId)
            ->where('category', FinancialPosition::CATEGORY_LOAN);

        $savingsBase = FinancialPosition::query()
            ->where('company_id', $companyId)
            ->where('category', FinancialPosition::CATEGORY_SAVINGS);

        $pendingJournals = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('status', JournalEntry::STATUS_PENDING)
            ->count();

        $groupsCount = MemberGroup::query()
            ->where('company_id', $companyId)
            ->count();

        return Inertia::render('Banking/OperationsHub', [
            'stats' => [
                'members_approved' => $membersApproved,
                'loan_accounts' => (clone $loanBase)->count(),
                'loan_outstanding_cents' => (int) (clone $loanBase)->sum('principal_cents'),
                'savings_accounts' => (clone $savingsBase)->count(),
                'savings_principal_cents' => (int) (clone $savingsBase)->sum('principal_cents'),
                'pending_journals' => $pendingJournals,
                'member_groups' => $groupsCount,
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }
}
