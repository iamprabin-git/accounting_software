<?php

namespace App\Filament\Resources\InventoryItems\Pages;

use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Models\InventoryItem;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryItem extends EditRecord
{
    protected static string $resource = InventoryItemResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['valuation_method'] = $data['valuation_method'] ?? InventoryItem::VALUATION_FIFO;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
