<?php

namespace App\Filament\Resources\FinancialPositions\Tables;

use App\Models\FinancialPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinancialPositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('principal_cents')
                    ->label('Principal')
                    ->money('NPR', divideBy: 100),
                TextColumn::make('annual_interest_rate_percent')
                    ->label('Rate %')
                    ->numeric(decimalPlaces: 4),
                TextColumn::make('annual_interest')
                    ->label('Interest / yr')
                    ->getStateUsing(fn (FinancialPosition $record): int => $record->annualInterestCents())
                    ->money('NPR', divideBy: 100),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category')
                    ->options([
                        FinancialPosition::CATEGORY_LOAN => 'Loan',
                        FinancialPosition::CATEGORY_INVESTMENT => 'Investment',
                        FinancialPosition::CATEGORY_SAVINGS => 'Savings',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
