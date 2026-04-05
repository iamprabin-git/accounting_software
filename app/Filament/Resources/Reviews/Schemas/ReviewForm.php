<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Models\Review;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('author_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('author_email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('title')
                    ->maxLength(255),
                Textarea::make('body')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('rating')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->default(5)
                    ->required(),
                Select::make('status')
                    ->options([
                        Review::STATUS_PENDING => 'Pending',
                        Review::STATUS_APPROVED => 'Approved',
                        Review::STATUS_REJECTED => 'Rejected',
                    ])
                    ->default(Review::STATUS_PENDING)
                    ->required(),
                Select::make('company_id')
                    ->label('Related company (optional)')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Textarea::make('admin_notes')
                    ->label('Internal notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }
}
