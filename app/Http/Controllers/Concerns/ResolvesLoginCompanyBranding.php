<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use Illuminate\Http\Request;

trait ResolvesLoginCompanyBranding
{
    /**
     * Branding for guest auth screens when `company_id` is present (query, form, or old input).
     *
     * @return array{loginBranding: ?array{name: string, logo_url: ?string}, loginCompanyId: ?int}
     */
    protected function loginCompanyBrandingProps(Request $request): array
    {
        $raw = $request->query('company_id');
        if ($raw === null || $raw === '') {
            $raw = old('company_id', $request->input('company_id'));
        }
        if ($raw === null || $raw === '') {
            return ['loginBranding' => null, 'loginCompanyId' => null];
        }

        $companyId = filter_var($raw, FILTER_VALIDATE_INT);
        if ($companyId === false || $companyId < 1) {
            return ['loginBranding' => null, 'loginCompanyId' => null];
        }

        $company = Company::query()->find($companyId);
        if ($company === null) {
            return ['loginBranding' => null, 'loginCompanyId' => null];
        }

        return [
            'loginBranding' => [
                'name' => $company->name,
                'logo_url' => $company->logoPublicUrl(),
            ],
            'loginCompanyId' => $company->id,
        ];
    }
}
