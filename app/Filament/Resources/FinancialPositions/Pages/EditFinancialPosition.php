<?php

namespace App\Filament\Resources\FinancialPositions\Pages;

use App\Filament\Resources\FinancialPositions\FinancialPositionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFinancialPosition extends EditRecord
{
    protected static string $resource = FinancialPositionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['principal'] = isset($data['principal_cents'])
            ? round(((int) $data['principal_cents']) / 100, 2)
            : 0;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('principal', $data)) {
            $data['principal_cents'] = (int) round(((float) $data['principal']) * 100);
            unset($data['principal']);
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
