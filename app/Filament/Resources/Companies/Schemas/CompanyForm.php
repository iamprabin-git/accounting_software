<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\Company;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('plan')
                    ->label('Product tier')
                    ->options(Company::planLabels())
                    ->required()
                    ->default(Company::PLAN_STARTER)
                    ->live()
                    ->helperText('CRM (sales pipeline) is available on Professional and Enterprise. Starter is accounting-only.'),
                Toggle::make('feature_inventory_enabled')
                    ->label('Inventory enabled')
                    ->helperText('Enterprise only: allow stock and inventory screens. Professional always includes inventory; Starter never does.')
                    ->default(true)
                    ->visible(fn (Get $get): bool => $get('plan') === Company::PLAN_ENTERPRISE),
                Toggle::make('feature_members_enabled')
                    ->label('Members & member portal enabled')
                    ->helperText('Enterprise only: members, customer messaging, and the end-user portal.')
                    ->default(true)
                    ->visible(fn (Get $get): bool => $get('plan') === Company::PLAN_ENTERPRISE),
                Textarea::make('address')
                    ->label('Address')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Street, city, postal code'),
                TextInput::make('phone')
                    ->label('Contact number')
                    ->tel()
                    ->maxLength(64),
                FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->disk('public')
                    ->directory('company-logos')
                    ->imageResizeMode('contain')
                    ->maxSize(2048)
                    ->nullable()
                    ->helperText('Shown on printed journals and financial reports.'),
            ]);
    }
}
