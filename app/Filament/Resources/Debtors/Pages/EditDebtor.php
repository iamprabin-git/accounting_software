<?php

namespace App\Filament\Resources\Debtors\Pages;

use App\Filament\Resources\Debtors\DebtorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDebtor extends EditRecord
{
    protected static string $resource = DebtorResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['balance'] = isset($data['balance_cents'])
            ? round(((int) $data['balance_cents']) / 100, 2)
            : 0;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('balance', $data)) {
            $data['balance_cents'] = (int) round(((float) $data['balance']) * 100);
            unset($data['balance']);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
