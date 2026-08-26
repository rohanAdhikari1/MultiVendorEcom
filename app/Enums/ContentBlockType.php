<?php

namespace App\Enums;

enum ContentBlockType: string
{
    case Hero = 'hero';
    case Banner = 'banner';
    case FeaturedCategories = 'featured_categories';
    case FeaturedProducts = 'featured_products';
    case FeaturedVendors = 'featured_vendors';
    case PromoGrid = 'promo_grid';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::Banner => 'Banner',
            self::FeaturedCategories => 'Featured Categories',
            self::FeaturedProducts => 'Featured Products',
            self::FeaturedVendors => 'Featured Vendors',
            self::PromoGrid => 'Promo Grid',
        };
    }
}
