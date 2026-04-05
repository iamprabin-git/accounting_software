<?php

namespace App\Filament\Resources\InventoryItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sku')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('valuation_method')
                    ->label('Method')
                    ->formatStateUsing(fn (?string $state): string => $state === 'lifo' ? 'LIFO' : 'FIFO'),
                TextColumn::make('quantity')
                    ->numeric(decimalPlaces: 4),
                TextColumn::make('unit_cost_cents')
                    ->label('Unit cost')
                    ->money('USD', divideBy: 100),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->searchable()
                    ->preload(),
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
