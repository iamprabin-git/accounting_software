<?php

namespace App\Filament\Resources\CrmActivities\Schemas;

use App\Models\CrmActivity;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CrmActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->options(CrmActivity::typeLabels())
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->rows(4)
                    ->columnSpanFull(),
                DateTimePicker::make('due_at')
                    ->nullable(),
                DateTimePicker::make('completed_at')
                    ->label('Completed at')
                    ->nullable(),
            ]);
    }
}
