<?php

namespace App\Filament\Resources\PlatformAdmins\Pages;

use App\Filament\Resources\PlatformAdmins\PlatformAdminResource;
use App\Models\User;
use App\Support\EmailAddress;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePlatformAdmin extends CreateRecord
{
    protected static string $resource = PlatformAdminResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            throw ValidationException::withMessages([
                'data.password' => __('A password is required for new platform admins.'),
            ]);
        }

        if (isset($data['email'])) {
            $data['email'] = EmailAddress::normalize((string) $data['email']) ?? '';
        }

        $data['role'] = User::ROLE_ADMIN;
        $data['company_id'] = null;

        return $data;
    }
}
