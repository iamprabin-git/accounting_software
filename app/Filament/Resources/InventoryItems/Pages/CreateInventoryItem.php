<?php

namespace App\Filament\Resources\InventoryItems\Pages;

use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Models\InventoryItem;
use App\Services\InventoryStockService;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryItem extends CreateRecord
{
    protected static string $resource = InventoryItemResource::class;

    protected float $openingQuantity = 0.0;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->openingQuantity = (float) ($data['quantity'] ?? 0);

        $data['valuation_method'] = $data['valuation_method'] ?? InventoryItem::VALUATION_FIFO;
        $data['unit_cost_cents'] = (int) round(((float) ($data['unit_cost'] ?? 0)) * 100);
        unset($data['unit_cost']);
        $data['quantity'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->openingQuantity <= 0) {
            return;
        }

        $record = $this->record->fresh();
        $unitCostCents = (int) $record->unit_cost_cents;

        $record->updateQuietly(['unit_cost_cents' => 0]);

        app(InventoryStockService::class)->recordPurchase(
            $record->fresh(),
            $this->openingQuantity,
            $unitCostCents,
            now()->toDateString(),
            __('Opening balance'),
            null,
            null,
        );
    }
}
