<?php

namespace App\Filament\Resources\CrmContacts\Schemas;

use App\Models\CrmAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CrmContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('crm_account_id')
                    ->label('Account')
                    ->options(fn (Get $get): array => CrmAccount::query()
                        ->when(
                            filled($get('company_id')),
                            fn (Builder $q) => $q->where('company_id', $get('company_id')),
                            fn (Builder $q) => $q->whereRaw('1 = 0'),
                        )
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->nullable(),
                TextInput::make('first_name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('last_name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(64),
                TextInput::make('job_title')
                    ->maxLength(120),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
