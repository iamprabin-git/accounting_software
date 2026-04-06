<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveBankingApiCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->isEndUser()) {
            return response()->json(['message' => 'Not allowed for this role.'], 403);
        }

        if (! $user->canViewAccountingReports()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($user->isAdmin()) {
            $raw = $request->input('company_id');
            if ($raw === null || $raw === '') {
                $raw = $request->header('X-Company-Id');
            }
            $companyId = (int) $raw;
            if ($companyId <= 0) {
                return response()->json([
                    'message' => 'Admin requests must include company_id (query, JSON body, or X-Company-Id header).',
                ], 422);
            }
        } else {
            $companyId = (int) ($user->company_id ?? 0);
            if ($companyId <= 0) {
                return response()->json(['message' => 'User has no company context.'], 403);
            }
        }

        $company = Company::query()->find($companyId);
        if ($company === null) {
            return response()->json(['message' => 'Company not found.'], 404);
        }

        if (! $company->allowsFinanceSuite() || ! $company->allowsMembersModule()) {
            return response()->json([
                'message' => 'Core banking API requires Enterprise plan with members and finance.',
            ], 403);
        }

        $request->attributes->set('banking_company', $company);

        return $next($request);
    }
}
