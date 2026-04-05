<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\Debtor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DebtorController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Debtor::class);

        $company = $this->accountingCompany($request);

        $query = Debtor::query()->where('company_id', $company->id)->orderBy('name');

        $totalCents = (clone $query)->sum('balance_cents');

        $debtors = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Debtor $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'reference' => $d->reference,
                'balance_cents' => (int) $d->balance_cents,
                'notes' => $d->notes,
            ]);

        return Inertia::render('Accounting/Debtors/Index', [
            'debtors' => $debtors,
            'total_balance_cents' => (int) $totalCents,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Debtor::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Accounting/Debtors/Create', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Debtor::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64'],
            'balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Debtor::query()->create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'reference' => $validated['reference'] ?? null,
            'balance_cents' => (int) round(((float) $validated['balance']) * 100),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('debtors.index', $this->companyQuery($request))
            ->with('status', __('Debtor saved.'));
    }

    public function edit(Request $request, int $debtor): Response
    {
        $company = $this->accountingCompany($request);

        $record = Debtor::query()
            ->where('company_id', $company->id)
            ->findOrFail($debtor);

        $this->authorize('update', $record);

        return Inertia::render('Accounting/Debtors/Edit', [
            'debtor' => [
                'id' => $record->id,
                'name' => $record->name,
                'reference' => $record->reference,
                'balance' => round(((int) $record->balance_cents) / 100, 2),
                'notes' => $record->notes,
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, int $debtor): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = Debtor::query()
            ->where('company_id', $company->id)
            ->findOrFail($debtor);

        $this->authorize('update', $record);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64'],
            'balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record->update([
            'name' => $validated['name'],
            'reference' => $validated['reference'] ?? null,
            'balance_cents' => (int) round(((float) $validated['balance']) * 100),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('debtors.index', $this->companyQuery($request))
            ->with('status', __('Debtor updated.'));
    }

    public function destroy(Request $request, int $debtor): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = Debtor::query()
            ->where('company_id', $company->id)
            ->findOrFail($debtor);

        $this->authorize('delete', $record);

        $record->delete();

        return redirect()->route('debtors.index', $this->companyQuery($request))
            ->with('status', __('Debtor removed.'));
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
     * @return array<string, mixed>
     */
    private function companyQuery(Request $request): array
    {
        if ($request->user()->isAdmin()) {
            return ['company_id' => $this->accountingCompany($request)->id];
        }

        return [];
    }
}
