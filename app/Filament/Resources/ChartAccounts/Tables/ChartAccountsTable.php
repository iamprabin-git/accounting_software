<?php

namespace App\Filament\Resources\ChartAccounts\Tables;

use App\Models\ChartAccount;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ChartAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Created by')
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('template.code')
                    ->label('Template')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('approval_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ChartAccount::STATUS_APPROVED => 'success',
                        ChartAccount::STATUS_PENDING => 'warning',
                        default => 'gray',
                    }),
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
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('approval_status')
                    ->options([
                        ChartAccount::STATUS_APPROVED => 'Approved',
                        ChartAccount::STATUS_PENDING => 'Pending',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ChartAccount $record): bool => $record->approval_status === ChartAccount::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->action(function (ChartAccount $record): void {
                        $record->update([
                            'approval_status' => ChartAccount::STATUS_APPROVED,
                            'approved_at' => now(),
                            'approved_by_user_id' => null,
                            'approved_by_admin_id' => auth('admin')->id(),
                        ]);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
