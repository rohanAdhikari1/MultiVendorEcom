<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product details')
                    ->description('Managed by the vendor from their own dashboard in a later phase. Shown here for context only.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->disabled(),
                        TextInput::make('slug')
                            ->required()
                            ->disabled(),
                        Select::make('vendor_id')
                            ->relationship('vendor', 'shop_name')
                            ->label('Vendor')
                            ->disabled(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label('Category')
                            ->disabled(),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->disabled(),
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('Rs.')
                            ->disabled(),
                        TextInput::make('compare_at_price')
                            ->numeric()
                            ->prefix('Rs.')
                            ->disabled(),
                        TextInput::make('stock_quantity')
                            ->numeric()
                            ->disabled(),
                        Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Moderation')
                    ->description('These are the only fields an admin may change from this panel.')
                    ->schema([
                        Select::make('status')
                            ->options(ProductStatus::class)
                            ->required(),
                        Toggle::make('is_featured'),
                    ])
                    ->columns(2),
            ]);
    }
}
