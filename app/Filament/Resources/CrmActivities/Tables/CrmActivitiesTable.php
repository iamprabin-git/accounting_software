<?php

namespace App\Filament\Resources\CrmActivities\Tables;

use App\Models\CrmAccount;
use App\Models\CrmActivity;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CrmActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Organization')
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CrmActivity::typeLabels()[$state] ?? $state),
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('related')
                    ->label('Related')
                    ->state(function (CrmActivity $record): string {
                        $subject = $record->subject;
                        if (! $subject) {
                            return '—';
                        }

                        return match (true) {
                            $subject instanceof CrmAccount => $subject->name,
                            $subject instanceof CrmContact => $subject->fullName(),
                            $subject instanceof CrmOpportunity => $subject->name,
                            default => class_basename($subject),
                        };
                    }),
                TextColumn::make('due_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                IconColumn::make('completed_at')
                    ->label('Done')
                    ->boolean()
                    ->getStateUsing(fn (CrmActivity $record): bool => $record->completed_at !== null),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->options(CrmActivity::typeLabels()),
                TernaryFilter::make('completed')
                    ->label('Completed')
                    ->queries(
                        fn ($q) => $q->whereNotNull('completed_at'),
                        fn ($q) => $q->whereNull('completed_at'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('due_at', 'desc');
    }
}
