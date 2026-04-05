<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Models\Creditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreditorController extends Controller
{
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Creditor::class);

        $company = $this->accountingCompany($request);

        $query = Creditor::query()->where('company_id', $company->id)->orderBy('name');

        $totalCents = (clone $query)->sum('balance_cents');

        $creditors = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Creditor $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'reference' => $c->reference,
                'balance_cents' => (int) $c->balance_cents,
                'notes' => $c->notes,
            ]);

        return Inertia::render('Accounting/Creditors/Index', [
            'creditors' => $creditors,
            'total_balance_cents' => (int) $totalCents,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Creditor::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Accounting/Creditors/Create', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Creditor::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64'],
            'balance' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Creditor::query()->create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'reference' => $validated['reference'] ?? null,
            'balance_cents' => (int) round(((float) $validated['balance']) * 100),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('creditors.index', $this->companyQuery($request))
            ->with('status', __('Creditor saved.'));
    }

    public function edit(Request $request, int $creditor): Response
    {
        $company = $this->accountingCompany($request);

        $record = Creditor::query()
            ->where('company_id', $company->id)
            ->findOrFail($creditor);

        $this->authorize('update', $record);

        return Inertia::render('Accounting/Creditors/Edit', [
            'creditor' => [
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

    public function update(Request $request, int $creditor): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = Creditor::query()
            ->where('company_id', $company->id)
            ->findOrFail($creditor);

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

        return redirect()->route('creditors.index', $this->companyQuery($request))
            ->with('status', __('Creditor updated.'));
    }

    public function destroy(Request $request, int $creditor): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = Creditor::query()
            ->where('company_id', $company->id)
            ->findOrFail($creditor);

        $this->authorize('delete', $record);

        $record->delete();

        return redirect()->route('creditors.index', $this->companyQuery($request))
            ->with('status', __('Creditor removed.'));
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
