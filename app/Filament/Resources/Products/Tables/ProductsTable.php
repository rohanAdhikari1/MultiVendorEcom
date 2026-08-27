<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\VendorProfile;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primaryImage.path')
                    ->label('')
                    ->disk('public')
                    ->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor.shop_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('price')
                    ->formatStateUsing(fn (string $state): string => 'Rs. '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::Draft => 'gray',
                        ProductStatus::Published => 'success',
                        ProductStatus::Archived => 'danger',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->label()),
                ToggleColumn::make('is_featured'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->options(fn (): array => VendorProfile::query()->pluck('shop_name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn (): array => Category::query()->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('status')
                    ->options(ProductStatus::class),
                TernaryFilter::make('is_featured'),
            ])
            ->searchable(['sku'])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
