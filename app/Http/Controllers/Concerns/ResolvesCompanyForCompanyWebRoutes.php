<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use Illuminate\Http\Request;

trait ResolvesCompanyForCompanyWebRoutes
{
    protected function companyForCompanyWebRoutes(Request $request): Company
    {
        $user = $request->user();
        abort_unless(
            $user !== null && ($user->isCompany() || $user->isAdmin() || $user->isStaff()),
            403,
        );

        $company = Company::resolvedForWebRequest($request);
        abort_unless($company !== null, 404);

        if ($user->isCompany() || $user->isStaff()) {
            abort_unless((int) $user->company_id === (int) $company->id, 403);
        }

        return $company;
    }

    /**
     * @return array<string, int>
     */
    protected function companyIdQueryForRedirect(Request $request, Company $company): array
    {
        $user = $request->user();
        if ($user !== null && $user->isAdmin()) {
            return ['company_id' => $company->id];
        }

        return [];
    }
}
