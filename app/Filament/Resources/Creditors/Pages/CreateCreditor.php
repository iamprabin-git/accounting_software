<?php

namespace App\Filament\Resources\Creditors\Pages;

use App\Filament\Resources\Creditors\CreditorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCreditor extends CreateRecord
{
    protected static string $resource = CreditorResource::class;

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
