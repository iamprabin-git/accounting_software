<?php

namespace App\Filament\Resources\CrmOpportunities\Schemas;

use App\Models\CrmAccount;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CrmOpportunityForm
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
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('stage')
                    ->options(CrmOpportunity::stageLabels())
                    ->default(CrmOpportunity::STAGE_LEAD)
                    ->required(),
                TextInput::make('amount')
                    ->label('Amount (dollars)')
                    ->numeric()
                    ->default(0),
                TextInput::make('probability')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->nullable(),
                DatePicker::make('expected_close_date')
                    ->nullable(),
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
                Select::make('crm_contact_id')
                    ->label('Contact')
                    ->options(fn (Get $get): array => CrmContact::query()
                        ->when(
                            filled($get('company_id')),
                            fn (Builder $q) => $q->where('company_id', $get('company_id')),
                            fn (Builder $q) => $q->whereRaw('1 = 0'),
                        )
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(fn (CrmContact $c) => [$c->id => $c->fullName()])
                        ->all())
                    ->searchable()
                    ->nullable(),
                Select::make('owner_user_id')
                    ->label('Owner')
                    ->options(fn (Get $get): array => User::query()
                        ->when(
                            filled($get('company_id')),
                            fn (Builder $q) => $q->where('company_id', $get('company_id')),
                            fn (Builder $q) => $q->whereRaw('1 = 0'),
                        )
                        ->whereIn('role', [User::ROLE_COMPANY, User::ROLE_STAFF])
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->nullable(),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
