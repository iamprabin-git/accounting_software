<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\EmailAddress;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            throw ValidationException::withMessages([
                'data.password' => __('A password is required for new users.'),
            ]);
        }

        if (isset($data['email'])) {
            $data['email'] = EmailAddress::normalize((string) $data['email']) ?? '';
        }

        $data['role'] = User::ROLE_COMPANY;

        if (empty($data['company_id'] ?? null)) {
            throw ValidationException::withMessages([
                'data.company_id' => __('Select the organization this company owner belongs to.'),
            ]);
        }

        return $data;
    }
}
