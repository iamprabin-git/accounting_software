<?php

namespace App\Filament\Resources\ChartAccountTemplates\Schemas;

use App\Models\ChartAccountTemplate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ChartAccountTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(32)
                    ->unique(ChartAccountTemplate::class, ignoreRecord: true),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        ChartAccountTemplate::TYPE_ASSET => 'Asset',
                        ChartAccountTemplate::TYPE_LIABILITY => 'Liability',
                        ChartAccountTemplate::TYPE_EQUITY => 'Equity',
                        ChartAccountTemplate::TYPE_REVENUE => 'Revenue',
                        ChartAccountTemplate::TYPE_EXPENSE => 'Expense',
                    ])
                    ->required(),
                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->label('Active (shown to companies)'),
            ]);
    }
}
