<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Concerns\AssertsNoCompanyHoliday;
use App\Http\Controllers\Concerns\ResolvesAccountingCompany;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\InventoryStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class InventoryItemController extends Controller
{
    use AssertsNoCompanyHoliday;
    use ResolvesAccountingCompany;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', InventoryItem::class);

        $company = $this->accountingCompany($request);

        $items = InventoryItem::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (InventoryItem $item) => [
                'id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity' => (string) $item->quantity,
                'unit_cost_cents' => (int) $item->unit_cost_cents,
                'valuation_method' => $item->valuation_method,
                'value_at_cost_cents' => $item->valueAtCostCents(),
                'notes' => $item->notes,
            ]);

        return Inertia::render('Accounting/Inventory/Index', [
            'items' => $items,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', InventoryItem::class);

        $company = $this->accountingCompany($request);

        return Inertia::render('Accounting/Inventory/Create', [
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', InventoryItem::class);

        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'valuation_method' => ['required', Rule::in(InventoryItem::valuationMethods())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $openingQty = (float) $validated['quantity'];
        $unitCostCents = (int) round(((float) $validated['unit_cost']) * 100);

        $item = InventoryItem::query()->create([
            'company_id' => $company->id,
            'sku' => $validated['sku'] ?? null,
            'name' => $validated['name'],
            'quantity' => 0,
            'unit_cost_cents' => 0,
            'valuation_method' => $validated['valuation_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($openingQty > 0) {
            try {
                app(InventoryStockService::class)->recordPurchase(
                    $item,
                    $openingQty,
                    $unitCostCents,
                    now()->toDateString(),
                    __('Opening balance'),
                    null,
                    $request->user(),
                );
            } catch (InvalidArgumentException $e) {
                $item->delete();

                return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
            }
        } else {
            $item->update([
                'unit_cost_cents' => $unitCostCents,
            ]);
        }

        return redirect()->route('inventory.show', array_merge(
            ['item' => $item->id],
            $this->companyQuery($request),
        ))->with('status', __('Item saved.'));
    }

    public function show(Request $request, int $item): Response
    {
        $company = $this->accountingCompany($request);

        $record = InventoryItem::query()
            ->where('company_id', $company->id)
            ->findOrFail($item);

        $this->authorize('view', $record);

        $lots = $record->lots()
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->get()
            ->map(fn ($lot) => [
                'id' => $lot->id,
                'quantity_remaining' => (string) $lot->quantity_remaining,
                'quantity_original' => (string) $lot->quantity_original,
                'unit_cost_cents' => (int) $lot->unit_cost_cents,
                'value_remaining_cents' => $lot->valueRemainingAtCostCents(),
                'received_at' => $lot->received_at->toDateString(),
                'reference' => $lot->reference,
            ]);

        $movements = $record->movements()
            ->with('user:id,name')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'quantity' => (string) $m->quantity,
                'transaction_date' => $m->transaction_date->toDateString(),
                'total_cost_cents' => (int) $m->total_cost_cents,
                'reference' => $m->reference,
                'notes' => $m->notes,
                'user_name' => $m->user?->name,
            ]);

        return Inertia::render('Accounting/Inventory/Show', [
            'item' => [
                'id' => $record->id,
                'sku' => $record->sku,
                'name' => $record->name,
                'quantity' => (string) $record->quantity,
                'unit_cost_cents' => (int) $record->unit_cost_cents,
                'valuation_method' => $record->valuation_method,
                'value_at_cost_cents' => $record->valueAtCostCents(),
                'notes' => $record->notes,
            ],
            'lots' => $lots,
            'movements' => $movements,
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function recordPurchase(Request $request, int $item): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = InventoryItem::query()
            ->where('company_id', $company->id)
            ->findOrFail($item);

        $this->authorize('update', $record);

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->assertNoCompanyHoliday($company->id, (string) $validated['transaction_date']);

        try {
            app(InventoryStockService::class)->recordPurchase(
                $record,
                (float) $validated['quantity'],
                (int) round(((float) $validated['unit_cost']) * 100),
                $validated['transaction_date'],
                $validated['reference'] ?? null,
                $validated['notes'] ?? null,
                $request->user(),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('status', __('Purchase recorded.'));
    }

    public function recordSale(Request $request, int $item): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = InventoryItem::query()
            ->where('company_id', $company->id)
            ->findOrFail($item);

        $this->authorize('update', $record);

        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'transaction_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->assertNoCompanyHoliday($company->id, (string) $validated['transaction_date']);

        try {
            app(InventoryStockService::class)->recordSale(
                $record,
                (float) $validated['quantity'],
                $validated['transaction_date'],
                $validated['reference'] ?? null,
                $validated['notes'] ?? null,
                $request->user(),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('status', __('Sale recorded. Stock reduced at :method cost.', [
            'method' => strtoupper($record->valuation_method),
        ]));
    }

    public function edit(Request $request, int $item): Response
    {
        $company = $this->accountingCompany($request);

        $record = InventoryItem::query()
            ->where('company_id', $company->id)
            ->findOrFail($item);

        $this->authorize('update', $record);

        return Inertia::render('Accounting/Inventory/Edit', [
            'item' => [
                'id' => $record->id,
                'sku' => $record->sku,
                'name' => $record->name,
                'valuation_method' => $record->valuation_method,
                'notes' => $record->notes,
            ],
            'valuationOptions' => [
                ['value' => InventoryItem::VALUATION_FIFO, 'label' => 'FIFO'],
                ['value' => InventoryItem::VALUATION_LIFO, 'label' => 'LIFO'],
            ],
            'companies' => $this->accountingCompanyOptionsForAdmin($request),
            'currentCompanyId' => $company->id,
        ]);
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = InventoryItem::query()
            ->where('company_id', $company->id)
            ->findOrFail($item);

        $this->authorize('update', $record);

        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'valuation_method' => ['required', Rule::in(InventoryItem::valuationMethods())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record->update([
            'sku' => $validated['sku'] ?? null,
            'name' => $validated['name'],
            'valuation_method' => $validated['valuation_method'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('inventory.show', array_merge(
            ['item' => $record->id],
            $this->companyQuery($request),
        ))->with('status', __('Item updated.'));
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $this->validateAdminCompanySelection($request);

        $company = $this->accountingCompany($request);

        $record = InventoryItem::query()
            ->where('company_id', $company->id)
            ->findOrFail($item);

        $this->authorize('delete', $record);

        $record->delete();

        return redirect()->route('inventory.index', $this->companyQuery($request))
            ->with('status', __('Item deleted.'));
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
