<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user->isCompany() && $user->company_id, 403);

        $company = Company::query()->findOrFail($user->company_id);

        $inventoryAccounts = ChartAccount::query()
            ->where('company_id', $company->id)
            ->approvedForJournals()
            ->where('type', ChartAccount::TYPE_ASSET)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (ChartAccount $a) => [
                'id' => $a->id,
                'label' => $a->code.' — '.$a->name,
            ])
            ->all();

        return Inertia::render('Company/Profile/Edit', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'address' => $company->address ?? '',
                'phone' => $company->phone ?? '',
                'bank_payment_details' => $company->bank_payment_details ?? '',
                'payment_qr_url' => $company->paymentQrPublicUrl(),
                'portal_show_payment_details' => (bool) $company->portal_show_payment_details,
                'logo_url' => $company->logoPublicUrl(),
                'inventory_chart_account_id' => $company->inventory_chart_account_id,
            ],
            'inventoryAccountOptions' => $inventoryAccounts,
            'staffLoginUrl' => url('/login?company_id='.$company->id),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isCompany() && $user->company_id, 403);

        $company = Company::query()->findOrFail($user->company_id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:64'],
            'bank_payment_details' => ['nullable', 'string', 'max:10000'],
            'payment_qr' => ['nullable', 'image', 'max:2048'],
            'remove_payment_qr' => ['nullable', 'boolean'],
            'portal_show_payment_details' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'inventory_chart_account_id' => [
                'nullable',
                'integer',
                Rule::exists('chart_accounts', 'id')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                        ->where('approval_status', ChartAccount::STATUS_APPROVED)
                        ->where('type', ChartAccount::TYPE_ASSET)
                ),
            ],
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
            'bank_payment_details' => $validated['bank_payment_details'] ?? null,
            'portal_show_payment_details' => $request->has('portal_show_payment_details')
                ? $request->boolean('portal_show_payment_details')
                : $company->portal_show_payment_details,
            'inventory_chart_account_id' => $validated['inventory_chart_account_id'] ?? null,
        ]);

        $company->save();

        return Redirect::route('company.profile.edit')
            ->with('status', __('Company profile updated.'));
    }
}
