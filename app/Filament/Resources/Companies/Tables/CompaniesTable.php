<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Models\Company;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Company::PLAN_STARTER => 'Starter',
                        Company::PLAN_PROFESSIONAL => 'Professional',
                        Company::PLAN_ENTERPRISE => 'Enterprise',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Company::PLAN_STARTER => 'gray',
                        Company::PLAN_PROFESSIONAL => 'info',
                        Company::PLAN_ENTERPRISE => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
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
