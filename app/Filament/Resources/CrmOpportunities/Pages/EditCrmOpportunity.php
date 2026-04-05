<?php

namespace App\Filament\Resources\CrmOpportunities\Pages;

use App\Filament\Resources\CrmOpportunities\CrmOpportunityResource;
use Filament\Resources\Pages\EditRecord;

class EditCrmOpportunity extends EditRecord
{
    protected static string $resource = CrmOpportunityResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['amount_cents']) && $data['amount_cents'] !== null) {
            $data['amount'] = $data['amount_cents'] / 100;
        } else {
            $data['amount'] = '';
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['amount_cents'] = isset($data['amount']) && $data['amount'] !== ''
            ? (int) round((float) $data['amount'] * 100)
            : null;
        unset($data['amount']);

        return $data;
    }
}
