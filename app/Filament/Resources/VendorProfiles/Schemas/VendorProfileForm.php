<?php

namespace App\Filament\Resources\VendorProfiles\Schemas;

use App\Enums\VendorApplicationStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VendorProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Shop details')
                    ->description('Read-only context about the vendor. Edit the shop profile itself from the vendor\'s own dashboard in a later phase.')
                    ->schema([
                        TextInput::make('shop_name')
                            ->required()
                            ->disabled(),
                        TextInput::make('shop_slug')
                            ->required()
                            ->disabled(),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Owner')
                            ->disabled(),
                        Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disabled()
                            ->disk('public')
                            ->directory('vendors/logos'),
                        FileUpload::make('banner_path')
                            ->label('Banner')
                            ->image()
                            ->disabled()
                            ->disk('public')
                            ->directory('vendors/banners'),
                    ])
                    ->columns(2),
                Section::make('Moderation')
                    ->schema([
                        Select::make('status')
                            ->options(VendorApplicationStatus::class)
                            ->required(),
                        TextInput::make('commission_override_percent')
                            ->label('Commission override')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable()
                            ->helperText('Overrides the platform\'s default commission percentage (Settings > Site Settings) for this vendor only. Leave blank to use the platform default.'),
                        Textarea::make('rejected_reason')
                            ->label('Rejection reason')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
