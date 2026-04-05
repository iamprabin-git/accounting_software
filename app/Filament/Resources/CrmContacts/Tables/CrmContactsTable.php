<?php

namespace App\Filament\Resources\CrmContacts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Organization')
                    ->sortable(),
                TextColumn::make('account.name')
                    ->label('Account')
                    ->placeholder('—'),
                TextColumn::make('first_name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->searchable(),
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
