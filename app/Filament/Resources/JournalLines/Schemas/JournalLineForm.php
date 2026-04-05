<?php

namespace App\Filament\Resources\JournalLines\Schemas;

use App\Models\JournalEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JournalLineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('journal_entry_id')
                    ->relationship(
                        name: 'journalEntry',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn ($query) => $query->latest('transaction_date'),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (JournalEntry $record): string => sprintf(
                            '#%d · %s · %s',
                            $record->id,
                            $record->transaction_date->format('M j, Y'),
                            $record->reference ?: '—',
                        ),
                    )
                    ->required(),
                Select::make('chart_account_id')
                    ->relationship(
                        'chartAccount',
                        'name',
                        fn ($query) => $query->orderBy('code'),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn ($record): string => $record->code.' — '.$record->name,
                    )
                    ->required(),
                TextInput::make('debit_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('credit_cents')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('description'),
            ]);
    }
}
