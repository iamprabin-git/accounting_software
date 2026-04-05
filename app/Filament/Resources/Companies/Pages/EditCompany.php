<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleEnterpriseInventory')
                ->label(fn (): string => $this->record->feature_inventory_enabled
                    ? 'Turn off inventory (Enterprise)'
                    : 'Turn on inventory (Enterprise)')
                ->color(fn (): string => $this->record->feature_inventory_enabled ? 'warning' : 'success')
                ->visible(fn (): bool => $this->record->plan === Company::PLAN_ENTERPRISE)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'feature_inventory_enabled' => ! $this->record->feature_inventory_enabled,
                    ]);
                    $this->record->refresh();
                    $this->fillForm();
                }),
            Action::make('toggleEnterpriseMembers')
                ->label(fn (): string => $this->record->feature_members_enabled
                    ? 'Turn off members & portal (Enterprise)'
                    : 'Turn on members & portal (Enterprise)')
                ->color(fn (): string => $this->record->feature_members_enabled ? 'warning' : 'success')
                ->visible(fn (): bool => $this->record->plan === Company::PLAN_ENTERPRISE)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'feature_members_enabled' => ! $this->record->feature_members_enabled,
                    ]);
                    $this->record->refresh();
                    $this->fillForm();
                }),
            DeleteAction::make(),
        ];
    }
}
