<?php

namespace App\Filament\Resources\ContentBlocks\Schemas;

use App\Enums\ContentBlockType;
use App\Models\Category;
use App\Models\Product;
use App\Models\VendorProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ContentBlockForm
{
    /**
     * These form field keys are "virtual" — they don't map to a real column
     * on the model. Instead, each represents a piece of the single `payload`
     * JSON column, shaped differently depending on the selected `type`. See
     * ContentBlockPayloadMapper for how they're packed into/out of payload.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(ContentBlockType::class)
                    ->required()
                    ->live(),
                TextInput::make('title')
                    ->nullable(),
                TextInput::make('subtitle')
                    ->nullable(),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Section::make('Hero content')
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('content-blocks'),
                        TextInput::make('hero_cta_text')
                            ->label('CTA text'),
                        TextInput::make('hero_cta_url')
                            ->label('CTA URL')
                            ->url(),
                    ])
                    ->columns(3)
                    ->visible(fn (Get $get): bool => $get('type') === ContentBlockType::Hero->value),

                Section::make('Banner content')
                    ->schema([
                        FileUpload::make('banner_image')
                            ->label('Image')
                            ->image()
                            ->disk('public')
                            ->directory('content-blocks'),
                        TextInput::make('banner_link_url')
                            ->label('Link URL')
                            ->url(),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('type') === ContentBlockType::Banner->value),

                Section::make('Featured products')
                    ->schema([
                        Select::make('featured_product_ids')
                            ->label('Products')
                            ->options(fn (): array => Product::query()->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->visible(fn (Get $get): bool => $get('type') === ContentBlockType::FeaturedProducts->value),

                Section::make('Featured categories')
                    ->schema([
                        Select::make('featured_category_ids')
                            ->label('Categories')
                            ->options(fn (): array => Category::query()->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->visible(fn (Get $get): bool => $get('type') === ContentBlockType::FeaturedCategories->value),

                Section::make('Featured vendors')
                    ->schema([
                        Select::make('featured_vendor_ids')
                            ->label('Vendors')
                            ->options(fn (): array => VendorProfile::query()->pluck('shop_name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->preload(),
                    ])
                    ->visible(fn (Get $get): bool => $get('type') === ContentBlockType::FeaturedVendors->value),

                Section::make('Promo grid items')
                    ->schema([
                        Repeater::make('promo_items')
                            ->label('')
                            ->schema([
                                FileUpload::make('image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('content-blocks'),
                                TextInput::make('title'),
                                TextInput::make('link_url')
                                    ->label('Link URL')
                                    ->url(),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add item'),
                    ])
                    ->visible(fn (Get $get): bool => $get('type') === ContentBlockType::PromoGrid->value),
            ]);
    }
}
