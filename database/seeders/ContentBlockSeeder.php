<?php

namespace Database\Seeders;

use App\Enums\ContentBlockType;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\VendorProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds a realistic homepage so the storefront isn't blank out of the box.
 * References demo data created by MarketplaceDemoSeeder (run this after it).
 */
class ContentBlockSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        ContentBlock::create([
            'type' => ContentBlockType::Hero,
            'title' => 'Everything you need, from vendors you trust',
            'subtitle' => 'Shop electronics, fashion, home essentials, and more from independent sellers across Nepal.',
            'payload' => [
                'image' => null,
                'cta_text' => 'Start Shopping',
                'cta_url' => '/products',
            ],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ContentBlock::create([
            'type' => ContentBlockType::FeaturedCategories,
            'title' => 'Shop by Category',
            'subtitle' => null,
            'payload' => [
                'ids' => Category::query()->orderBy('sort_order')->pluck('id')->all(),
            ],
            'sort_order' => 2,
            'is_active' => true,
        ]);

        ContentBlock::create([
            'type' => ContentBlockType::FeaturedProducts,
            'title' => 'Featured Products',
            'subtitle' => 'Hand-picked picks from our marketplace',
            'payload' => [
                'ids' => Product::query()->where('is_featured', true)->pluck('id')->all(),
            ],
            'sort_order' => 3,
            'is_active' => true,
        ]);

        ContentBlock::create([
            'type' => ContentBlockType::FeaturedVendors,
            'title' => 'Meet Our Vendors',
            'subtitle' => null,
            'payload' => [
                'ids' => VendorProfile::query()->pluck('id')->all(),
            ],
            'sort_order' => 4,
            'is_active' => true,
        ]);
    }
}
