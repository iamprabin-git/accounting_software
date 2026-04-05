<?php

namespace App\Filament\Resources\Debtors\Pages;

use App\Filament\Resources\Debtors\DebtorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDebtor extends CreateRecord
{
    protected static string $resource = DebtorResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['balance_cents'] = (int) round(((float) ($data['balance'] ?? 0)) * 100);
        unset($data['balance']);

        return $data;
    }
}
