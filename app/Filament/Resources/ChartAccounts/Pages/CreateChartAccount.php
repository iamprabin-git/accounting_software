<?php

namespace App\Filament\Resources\ChartAccounts\Pages;

use App\Filament\Resources\ChartAccounts\ChartAccountResource;
use App\Models\ChartAccount;
use Filament\Resources\Pages\CreateRecord;

class CreateChartAccount extends CreateRecord
{
    protected static string $resource = ChartAccountResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['approval_status'] = ChartAccount::STATUS_APPROVED;
        $data['approved_at'] = now();
        $data['approved_by_admin_id'] = auth('admin')->id();

        return $data;
    }
}
