<?php

namespace App\Filament\Resources\ChartAccountTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChartAccountTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
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
