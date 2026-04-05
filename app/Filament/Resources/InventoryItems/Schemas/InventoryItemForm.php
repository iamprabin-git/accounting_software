<?php

namespace App\Filament\Resources\InventoryItems\Schemas;

use App\Models\InventoryItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryItemForm
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
                    ->required(),
                TextInput::make('sku')
                    ->maxLength(64)
                    ->label('SKU'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('valuation_method')
                    ->label('Costing for sales')
                    ->options([
                        InventoryItem::VALUATION_FIFO => 'FIFO',
                        InventoryItem::VALUATION_LIFO => 'LIFO',
                    ])
                    ->default(InventoryItem::VALUATION_FIFO)
                    ->required(),
                TextInput::make('quantity')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->visibleOn('create')
                    ->helperText('Opening quantity; further stock uses Purchases in the customer app.'),
                TextInput::make('unit_cost')
                    ->label('Unit cost (dollars)')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->visibleOn('create')
                    ->helperText('Opening layer cost; stored in cents.'),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
