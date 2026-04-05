<?php

namespace App\Filament\Resources\CrmAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CrmAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('industry')
                    ->maxLength(120),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(64),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Textarea::make('address')
                    ->rows(2)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
