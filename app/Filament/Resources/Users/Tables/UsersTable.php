<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('access_status')
                    ->label('Access')
                    ->state(fn (User $record): string => $record->customerAccessLabel())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Expired' => 'warning',
                        'Inactive' => 'danger',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active')
                    ->disabledClick(false),
                TextColumn::make('subscription_ends_at')
                    ->label('Subscription ends')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('is_active')
                    ->label('Account status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
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
