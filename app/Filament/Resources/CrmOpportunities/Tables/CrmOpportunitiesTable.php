<?php

namespace App\Filament\Resources\CrmOpportunities\Tables;

use App\Models\CrmOpportunity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmOpportunitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Organization')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('stage')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CrmOpportunity::stageLabels()[$state] ?? $state),
                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->money('USD', divideBy: 100)
                    ->placeholder('—'),
                TextColumn::make('account.name')
                    ->label('Account')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('stage')
                    ->options(CrmOpportunity::stageLabels()),
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
