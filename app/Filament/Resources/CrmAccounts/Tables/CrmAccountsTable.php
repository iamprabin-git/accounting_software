<?php

namespace App\Filament\Resources\CrmAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Organization')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('industry')
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->placeholder('—'),
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
