<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\ResolvesCompanyForCompanyWebRoutes;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    use ResolvesCompanyForCompanyWebRoutes;

    public function edit(Request $request): Response
    {
        $company = $this->companyForCompanyWebRoutes($request);

        return Inertia::render('Company/Profile/Edit', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'address' => $company->address ?? '',
                'phone' => $company->phone ?? '',
                'contact_email' => $company->contact_email ?? '',
                'bank_payment_details' => $company->bank_payment_details ?? '',
                'payment_qr_url' => $company->paymentQrPublicUrl(),
                'portal_show_payment_details' => (bool) $company->portal_show_payment_details,
                'logo_url' => $company->logoPublicUrl(),
            ],
            'staffLoginUrl' => url('/login?company_id='.$company->id),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageCompanyWebSettings(), 403);

        $company = $this->companyForCompanyWebRoutes($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:64'],
            'contact_email' => ['nullable', 'string', 'max:255', 'email'],
            'bank_payment_details' => ['nullable', 'string', 'max:10000'],
            'payment_qr' => ['nullable', 'image', 'max:2048'],
            'remove_payment_qr' => ['nullable', 'boolean'],
            'portal_show_payment_details' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $company->logo_path = $request->file('logo')->store('company-logos', 'public');
        } elseif ($request->boolean('remove_logo') && $company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $company->logo_path = null;
        }

        if ($request->hasFile('payment_qr')) {
            if ($company->payment_qr_path) {
                Storage::disk('public')->delete($company->payment_qr_path);
            }

            $company->payment_qr_path = $request->file('payment_qr')->store('company-payment-qr', 'public');
        } elseif ($request->boolean('remove_payment_qr') && $company->payment_qr_path) {
            Storage::disk('public')->delete($company->payment_qr_path);
            $company->payment_qr_path = null;
        }

        $company->fill([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'bank_payment_details' => $validated['bank_payment_details'] ?? null,
            'portal_show_payment_details' => $request->has('portal_show_payment_details')
                ? $request->boolean('portal_show_payment_details')
                : $company->portal_show_payment_details,
        ]);

        $company->save();

        return Redirect::route(
            'company.profile.edit',
            $this->companyIdQueryForRedirect($request, $company),
        )->with('status', __('Company profile updated.'));
    }
}
