<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialPosition;
use App\Models\JournalEntry;
use App\Models\Member;
use App\Models\MemberGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankingSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('banking_company');
        $companyId = $company->id;

        $loanBase = FinancialPosition::query()
            ->where('company_id', $companyId)
            ->where('category', FinancialPosition::CATEGORY_LOAN);

        $savingsBase = FinancialPosition::query()
            ->where('company_id', $companyId)
            ->where('category', FinancialPosition::CATEGORY_SAVINGS);

        return response()->json([
            'data' => [
                'members_approved' => Member::query()
                    ->where('company_id', $companyId)
                    ->where('status', Member::STATUS_APPROVED)
                    ->count(),
                'loan_accounts' => (clone $loanBase)->count(),
                'loan_outstanding_cents' => (int) (clone $loanBase)->sum('principal_cents'),
                'savings_accounts' => (clone $savingsBase)->count(),
                'savings_principal_cents' => (int) (clone $savingsBase)->sum('principal_cents'),
                'pending_journals' => JournalEntry::query()
                    ->where('company_id', $companyId)
                    ->where('status', JournalEntry::STATUS_PENDING)
                    ->count(),
                'member_groups' => MemberGroup::query()->where('company_id', $companyId)->count(),
            ],
            'meta' => [
                'company_id' => $companyId,
                'company_name' => $company->name,
            ],
        ]);
    }
}
