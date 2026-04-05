<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesAccountingCompany
{
    /**
     * Resolve the accounting company when available without aborting (e.g. dashboard when no companies exist).
     */
    protected function optionalAccountingCompany(Request $request): ?Company
    {
        return Company::resolvedForWebRequest($request);
    }

    protected function accountingCompany(Request $request): Company
    {
        $company = Company::resolvedForWebRequest($request);

        if ($company !== null) {
            return $company;
        }

        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            abort(404, __('No companies are available.'));
        }

        abort(403);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    protected function accountingCompanyOptionsForAdmin(Request $request): array
    {
        if (! $request->user()->isAdmin()) {
            return [];
        }

        return Company::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }

    /**
     * Letterhead data for print layouts (reports, journals, ledger).
     *
     * @return array{name: string, address: string, phone: string, logo_url: string|null}
     */
    protected function companyLetterhead(Company $company): array
    {
        return [
            'name' => $company->name,
            'address' => $company->address ?? '',
            'phone' => $company->phone ?? '',
            'logo_url' => $company->logoPublicUrl(),
        ];
    }

    /**
     * Bank instructions and optional payment QR image for the customer portal (end users).
     *
     * @return array{visible: bool, bank_payment_details: string|null, payment_qr_url: string|null}
     */
    protected function companyPaymentDetailsForPortal(Company $company): array
    {
        return [
            'visible' => (bool) $company->portal_show_payment_details,
            'bank_payment_details' => $company->bank_payment_details !== null && $company->bank_payment_details !== ''
                ? $company->bank_payment_details
                : null,
            'payment_qr_url' => $company->paymentQrPublicUrl(),
        ];
    }
}
