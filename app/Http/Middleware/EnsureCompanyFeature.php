<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $company = Company::resolvedForWebRequest($request);

        if (! $company) {
            return $this->deny($request);
        }

        $allowed = match ($feature) {
            'inventory' => $company->allowsInventory(),
            'members' => $company->allowsMembersModule(),
            'debtors_creditors' => $company->allowsDebtorsCreditors(),
            'finance' => $company->allowsFinanceSuite(),
            'crm' => $company->allowsCrm(),
            default => false,
        };

        if (! $allowed) {
            return $this->deny($request);
        }

        return $next($request);
    }

    protected function deny(Request $request): Response
    {
        $message = 'This feature is not enabled for your organization’s plan.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()
            ->route('dashboard')
            ->with('error', $message);
    }
}
