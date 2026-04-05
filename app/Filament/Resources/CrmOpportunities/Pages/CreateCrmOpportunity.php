<?php

namespace App\Filament\Resources\CrmOpportunities\Pages;

use App\Filament\Resources\CrmOpportunities\CrmOpportunityResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrmOpportunity extends CreateRecord
{
    protected static string $resource = CrmOpportunityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['amount_cents'] = isset($data['amount']) && $data['amount'] !== ''
            ? (int) round((float) $data['amount'] * 100)
            : null;
        unset($data['amount']);

        return $data;
    }
}
