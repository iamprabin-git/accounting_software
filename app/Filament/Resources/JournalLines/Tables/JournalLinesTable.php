<?php

namespace App\Filament\Resources\JournalLines\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JournalLinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('journalEntry.id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('chartAccount.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('debit_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('credit_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
