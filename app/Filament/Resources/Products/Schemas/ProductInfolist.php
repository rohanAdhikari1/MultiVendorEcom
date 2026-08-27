<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product details')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('vendor.shop_name')
                            ->label('Vendor'),
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->placeholder('—'),
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->placeholder('—'),
                        TextEntry::make('price')
                            ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2)),
                        TextEntry::make('compare_at_price')
                            ->label('Compare-at price')
                            ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : 'Rs. '.number_format((float) $state, 2)),
                        TextEntry::make('stock_quantity')
                            ->label('Stock'),
                        TextEntry::make('description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Moderation')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (ProductStatus $state): string => match ($state) {
                                ProductStatus::Draft => 'gray',
                                ProductStatus::Published => 'success',
                                ProductStatus::Archived => 'danger',
                            })
                            ->formatStateUsing(fn (ProductStatus $state): string => $state->label()),
                        IconEntry::make('is_featured')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Images')
                    ->schema([
                        RepeatableEntry::make('images')
                            ->label('')
                            ->schema([
                                ImageEntry::make('path')
                                    ->label('')
                                    ->disk('public')
                                    ->square(),
                            ])
                            ->columns(4)
                            ->grid(4),
                    ])
                    ->visible(fn (Product $record): bool => $record->images->isNotEmpty()),
                Section::make('Variants')
                    ->schema([
                        RepeatableEntry::make('variants')
                            ->label('')
                            ->schema([
                                TextEntry::make('sku')
                                    ->label('SKU')
                                    ->placeholder('—'),
                                TextEntry::make('price_override')
                                    ->label('Price override')
                                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : 'Rs. '.number_format((float) $state, 2)),
                                TextEntry::make('stock_quantity')
                                    ->label('Stock'),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn (Product $record): bool => $record->variants->isNotEmpty()),
            ]);
    }
}
