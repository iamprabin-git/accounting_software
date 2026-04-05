<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\EmailAddress;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
