<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author_name')
                    ->searchable(),
                TextColumn::make('rating')
                    ->sortable(),
                TextColumn::make('title')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Review::STATUS_APPROVED => 'success',
                        Review::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Review::STATUS_PENDING => 'Pending',
                        Review::STATUS_APPROVED => 'Approved',
                        Review::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Review $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->update([
                        'status' => Review::STATUS_APPROVED,
                    ])),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Review $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->update([
                        'status' => Review::STATUS_REJECTED,
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
