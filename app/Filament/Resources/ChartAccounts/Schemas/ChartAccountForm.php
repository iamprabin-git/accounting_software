<?php

namespace App\Filament\Resources\ChartAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ChartAccountForm
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
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'revenue' => 'Revenue',
                        'expense' => 'Expense',
                    ])
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
