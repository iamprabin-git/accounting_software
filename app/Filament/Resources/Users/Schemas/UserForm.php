<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Support\EmailAddress;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema, bool $companyOwnersOnly = false): Schema
    {
        $components = [
            TextInput::make('name')
                ->required(),
            TextInput::make('email')
                ->label('Email address')
                ->required()
                ->maxLength(255)
                ->rules([EmailAddress::laravelRule()]),
        ];

        if (! $companyOwnersOnly) {
            $components[] = Select::make('role')
                ->options([
                    User::ROLE_ADMIN => 'Platform admin',
                    User::ROLE_COMPANY => 'Company owner',
                    User::ROLE_STAFF => 'Staff',
                    User::ROLE_END_USER => 'End user',
                ])
                ->required()
                ->default(User::ROLE_END_USER)
                ->live();
        }

        $components[] = Select::make('company_id')
            ->label('Company')
            ->relationship('company', 'name')
            ->searchable()
            ->preload()
            ->visible(fn (Get $get): bool => $companyOwnersOnly || $get('role') !== User::ROLE_ADMIN)
            ->required(fn (Get $get): bool => $companyOwnersOnly || $get('role') !== User::ROLE_ADMIN);

        return $schema
            ->components(array_merge($components, [
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8),
                Toggle::make('is_active')
                    ->label('Account active')
                    ->helperText('When off, the user cannot sign in or use the customer app.')
                    ->default(true)
                    ->required(),
                DateTimePicker::make('subscription_ends_at')
                    ->label('Subscription ends')
                    ->seconds(false)
                    ->nullable()
                    ->helperText('Leave empty for no billing end date. After this moment, access is denied until the date is extended or the account is reactivated. Run the daily scheduler to auto-disable expired accounts.'),
            ]));
    }
}
