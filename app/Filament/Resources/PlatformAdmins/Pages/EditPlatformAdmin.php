<?php

namespace App\Filament\Resources\PlatformAdmins\Pages;

use App\Filament\Resources\PlatformAdmins\PlatformAdminResource;
use App\Models\User;
use App\Support\EmailAddress;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformAdmin extends EditRecord
{
    protected static string $resource = PlatformAdminResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }

        if (isset($data['email'])) {
            $data['email'] = EmailAddress::normalize((string) $data['email']) ?? '';
        }

        $data['role'] = User::ROLE_ADMIN;
        $data['company_id'] = null;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
