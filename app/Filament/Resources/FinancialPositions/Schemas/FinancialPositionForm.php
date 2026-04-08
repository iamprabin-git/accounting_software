<?php

namespace App\Filament\Resources\FinancialPositions\Schemas;

use App\Models\FinancialPosition;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FinancialPositionForm
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
                Select::make('category')
                    ->options([
                        FinancialPosition::CATEGORY_LOAN => 'Loan',
                        FinancialPosition::CATEGORY_INVESTMENT => 'Investment',
                        FinancialPosition::CATEGORY_SAVINGS => 'Savings',
                    ])
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('principal')
                    ->label('Principal / balance (NPR)')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('annual_interest_rate_percent')
                    ->label('Annual interest rate %')
                    ->numeric()
                    ->default(0)
                    ->required(),
                DatePicker::make('start_date')
                    ->nullable(),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
