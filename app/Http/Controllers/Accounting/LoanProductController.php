<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\LoanProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LoanProductController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LoanProduct::class);

        $company = $this->accountingCompany($request);

        $products = LoanProduct::query()
            ->where('company_id', $company->id)
            ->orderBy('product_code')
            ->get()
            ->map(fn (LoanProduct $p) => [
                'id' => $p->id,
                'product_code' => $p->product_code,
                'name' => $p->name,
                'default_annual_interest_rate_percent' => (string) $p->default_annual_interest_rate_percent,
                'is_active' => $p->is_active,
                'notes' => $p->notes,
            ]);

        return Inertia::render('Accounting/Finance/LoanProducts/Index', [
            'products' => $products,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', LoanProduct::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Accounting/Finance/LoanProducts/Create', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LoanProduct::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'product_code' => [
                'required',
                'string',
                'max:16',
                'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/',
                Rule::unique('loan_products', 'product_code')->where(
                    fn ($q) => $q->where('company_id', $company->id)
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'default_annual_interest_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        LoanProduct::query()->create([
            'company_id' => $company->id,
            'product_code' => $validated['product_code'],
            'name' => $validated['name'],
            'default_annual_interest_rate_percent' => $validated['default_annual_interest_rate_percent'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('finance.loan-products.index', $this->companyQuery($request))
            ->with('status', __('Loan product saved.'));
    }

    public function edit(Request $request, int $loanProduct): Response
    {
        $company = $this->accountingCompany($request);

        $record = LoanProduct::query()
            ->where('company_id', $company->id)
            ->findOrFail($loanProduct);

        $this->authorize('update', $record);

        return Inertia::render('Accounting/Finance/LoanProducts/Edit', [
            'product' => [
                'id' => $record->id,
                'product_code' => $record->product_code,
                'name' => $record->name,
                'default_annual_interest_rate_percent' => (string) $record->default_annual_interest_rate_percent,
                'notes' => $record->notes,
                'is_active' => $record->is_active,
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, int $loanProduct): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = LoanProduct::query()
            ->where('company_id', $company->id)
            ->findOrFail($loanProduct);

        $this->authorize('update', $record);

        $validated = $request->validate([
            'product_code' => [
                'required',
                'string',
                'max:16',
                'regex:/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/',
                Rule::unique('loan_products', 'product_code')
                    ->where(fn ($q) => $q->where('company_id', $company->id))
                    ->ignore($record->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'default_annual_interest_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record->update([
            'product_code' => $validated['product_code'],
            'name' => $validated['name'],
            'default_annual_interest_rate_percent' => $validated['default_annual_interest_rate_percent'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('finance.loan-products.index', $this->companyQuery($request))
            ->with('status', __('Loan product updated.'));
    }

    public function destroy(Request $request, int $loanProduct): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = LoanProduct::query()
            ->where('company_id', $company->id)
            ->findOrFail($loanProduct);

        $this->authorize('delete', $record);

        if ($record->financialPositions()->exists()) {
            return back()->withErrors([
                'product' => __('This product is linked to loan accounts. Deactivate it instead of deleting.'),
            ]);
        }

        $record->delete();

        return redirect()->route('finance.loan-products.index', $this->companyQuery($request))
            ->with('status', __('Loan product removed.'));
    }

    private function validateAdminCompanySelection(Request $request): void
    {
        if (! $request->user()->isAdmin()) {
            return;
        }

        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $request->session()->put('accounting_company_id', (int) $request->input('company_id'));
    }

    /**
     * @return array<string, int>
     */
    private function companyQuery(Request $request): array
    {
        if ($request->user()->isAdmin()) {
            return ['company_id' => $this->accountingCompany($request)->id];
        }

        return [];
    }
}
