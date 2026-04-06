<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankingMemberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Company $company */
        $company = $request->attributes->get('banking_company');

        $perPage = min(max((int) $request->query('per_page', 50), 1), 200);

        $paginator = Member::query()
            ->where('company_id', $company->id)
            ->orderByRaw('member_number IS NULL, member_number ASC')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Member $m) => [
                'id' => $m->id,
                'member_number' => $m->member_number,
                'name' => $m->name,
                'status' => $m->status,
                'email' => $m->email,
                'phone' => $m->phone,
            ])->values()->all(),
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
