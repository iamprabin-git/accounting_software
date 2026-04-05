<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Services\InventoryStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryStockFifoLifoTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifo_sale_consumes_oldest_layer_first(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        $service = app(InventoryStockService::class);

        $item = InventoryItem::query()->create([
            'company_id' => $company->id,
            'sku' => null,
            'name' => 'Widget',
            'quantity' => 0,
            'unit_cost_cents' => 0,
            'valuation_method' => InventoryItem::VALUATION_FIFO,
            'notes' => null,
        ]);

        $service->recordPurchase($item, 10.0, 100, '2026-01-01', null, null, $owner);
        $service->recordPurchase($item, 10.0, 300, '2026-02-01', null, null, $owner);

        $item->refresh();
        $this->assertSame('20.0000', (string) $item->quantity);

        $service->recordSale($item, 10.0, '2026-03-01', null, null, $owner);

        $item->refresh();
        $this->assertSame('10.0000', (string) $item->quantity);
        $this->assertSame(3000, $item->valueAtCostCents());

        $remainingLot = InventoryLot::query()
            ->where('inventory_item_id', $item->id)
            ->where('quantity_remaining', '>', 0)
            ->first();
        $this->assertNotNull($remainingLot);
        $this->assertSame(300, (int) $remainingLot->unit_cost_cents);

        $sale = InventoryMovement::query()
            ->where('inventory_item_id', $item->id)
            ->where('type', InventoryMovement::TYPE_SALE)
            ->first();
        $this->assertNotNull($sale);
        $this->assertSame(1000, (int) $sale->total_cost_cents);
    }

    public function test_lifo_sale_consumes_newest_layer_first(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();
        $service = app(InventoryStockService::class);

        $item = InventoryItem::query()->create([
            'company_id' => $company->id,
            'sku' => null,
            'name' => 'Gadget',
            'quantity' => 0,
            'unit_cost_cents' => 0,
            'valuation_method' => InventoryItem::VALUATION_LIFO,
            'notes' => null,
        ]);

        $service->recordPurchase($item, 10.0, 100, '2026-01-01', null, null, $owner);
        $service->recordPurchase($item, 10.0, 300, '2026-02-01', null, null, $owner);

        $service->recordSale($item, 10.0, '2026-03-01', null, null, $owner);

        $item->refresh();
        $this->assertSame('10.0000', (string) $item->quantity);
        $this->assertSame(1000, $item->valueAtCostCents());

        $remainingLot = InventoryLot::query()
            ->where('inventory_item_id', $item->id)
            ->where('quantity_remaining', '>', 0)
            ->first();
        $this->assertNotNull($remainingLot);
        $this->assertSame(100, (int) $remainingLot->unit_cost_cents);

        $sale = InventoryMovement::query()
            ->where('inventory_item_id', $item->id)
            ->where('type', InventoryMovement::TYPE_SALE)
            ->first();
        $this->assertNotNull($sale);
        $this->assertSame(3000, (int) $sale->total_cost_cents);
    }

    public function test_http_purchase_and_sale_update_closing_stock(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->companyOwner($company)->create();

        $this->actingAs($owner)->post(route('inventory.store', absolute: false), [
            'name' => 'Stocked',
            'sku' => null,
            'quantity' => '2',
            'unit_cost' => '5',
            'valuation_method' => 'fifo',
            'notes' => null,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $item = InventoryItem::query()->first();
        $this->assertNotNull($item);

        $this->actingAs($owner)
            ->post(route('inventory.purchase', ['item' => $item->id], absolute: false), [
                'quantity' => '3',
                'unit_cost' => '8',
                'transaction_date' => '2026-04-01',
            ])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('5.0000', (string) $item->quantity);

        $this->actingAs($owner)
            ->post(route('inventory.sale', ['item' => $item->id], absolute: false), [
                'quantity' => '4',
                'transaction_date' => '2026-04-02',
            ])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('1.0000', (string) $item->quantity);
        $this->assertGreaterThan(0, $item->valueAtCostCents());
    }
}
