<?php

namespace App\Filament\Resources\FinancialPositions\Pages;

use App\Filament\Resources\FinancialPositions\FinancialPositionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFinancialPosition extends CreateRecord
{
    protected static string $resource = FinancialPositionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['principal_cents'] = (int) round(((float) ($data['principal'] ?? 0)) * 100);
        unset($data['principal']);

        return $data;
    }
}
