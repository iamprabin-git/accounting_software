<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FinancialPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankingPositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('banking_company');

        $validated = $request->validate([
            'category' => ['nullable', 'string', Rule::in([FinancialPosition::CATEGORY_LOAN, FinancialPosition::CATEGORY_SAVINGS])],
        ]);

        $perPage = min(max((int) $request->query('per_page', 50), 1), 200);

        $q = FinancialPosition::query()
            ->where('company_id', $company->id)
            ->with(['member:id,member_number,name,status'])
            ->orderBy('category')
            ->orderBy('account_number');

        if (! empty($validated['category'])) {
            $q->where('category', $validated['category']);
        } else {
            $q->whereIn('category', [
                FinancialPosition::CATEGORY_LOAN,
                FinancialPosition::CATEGORY_SAVINGS,
            ]);
        }

        $paginator = $q->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(function (FinancialPosition $p) {
                return [
                    'id' => $p->id,
                    'category' => $p->category,
                    'title' => $p->title,
                    'account_number' => $p->account_number,
                    'principal_cents' => (int) $p->principal_cents,
                    'annual_interest_rate_percent' => (string) $p->annual_interest_rate_percent,
                    'start_date' => $p->start_date?->toDateString(),
                    'member' => $p->member ? [
                        'id' => $p->member->id,
                        'member_number' => $p->member->member_number,
                        'name' => $p->member->name,
                        'status' => $p->member->status,
                    ] : null,
                    'loan_product_id' => $p->loan_product_id,
                    'savings_product_id' => $p->savings_product_id,
                    'loan_workflow_status' => $p->loan_workflow_status,
                    'savings_workflow_status' => $p->savings_workflow_status,
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'company_id' => $company->id,
            ],
        ]);
    }
}
