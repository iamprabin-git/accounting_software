<?php

namespace App\Filament\Resources\JournalEntries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('user_id')
                    ->label('Created by (user)')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query, Get $get): Builder {
                            $companyId = $get('company_id');

                            if ($companyId === null) {
                                return $query->whereRaw('1 = 0');
                            }

                            return $query->where('company_id', $companyId);
                        },
                    )
                    ->required(),
                TextInput::make('reference'),
                TextInput::make('memo'),
                DatePicker::make('transaction_date')
                    ->required(),
            ]);
    }
}
