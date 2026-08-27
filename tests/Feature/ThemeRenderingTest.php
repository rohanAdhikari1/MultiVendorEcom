<?php

use App\Enums\ContentBlockType;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\VendorProfile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Registers a throwaway route (not routes/web.php, which a later phase
 * owns) that renders the storefront layout with the home content-block
 * renderer, and returns the URL to hit.
 */
function registerThemePreviewRoute(): string
{
    Route::middleware('web')->get('/__test-theme-preview', function () {
        return Blade::render(
            <<<'BLADE'
                <x-layouts.storefront>
                    <x-home-content-blocks />
                </x-layouts.storefront>
                BLADE
        );
    });

    return '/__test-theme-preview';
}

test('renders the full theme chain end to end for every content block type', function () {
    $vendor = VendorProfile::factory()->approved()->create([
        'shop_name' => 'Himalayan Bazaar',
    ]);

    $electronics = Category::factory()->create(['name' => 'Electronics']);
    $fashion = Category::factory()->create(['name' => 'Fashion']);

    $earbuds = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $electronics->id,
        'name' => 'Wireless Bluetooth Earbuds',
        'is_featured' => true,
    ]);
    $jacket = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $fashion->id,
        'name' => 'Women\'s Denim Jacket',
        'is_featured' => true,
    ]);

    ContentBlock::create([
        'type' => ContentBlockType::Hero,
        'title' => 'Big Season Sale',
        'subtitle' => 'Save big on every category',
        'payload' => [
            'image' => 'content-blocks/hero-banner.jpg',
            'cta_text' => 'Shop Now',
            'cta_url' => '/products',
        ],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    ContentBlock::create([
        'type' => ContentBlockType::Banner,
        'title' => 'Flash Sale',
        'payload' => [
            'image' => 'content-blocks/flash-sale.jpg',
            'link_url' => '/sale',
        ],
        'sort_order' => 2,
        'is_active' => true,
    ]);

    ContentBlock::create([
        'type' => ContentBlockType::FeaturedCategories,
        'title' => 'Shop by Category',
        'payload' => [
            'ids' => [$electronics->id, $fashion->id],
        ],
        'sort_order' => 3,
        'is_active' => true,
    ]);

    ContentBlock::create([
        'type' => ContentBlockType::FeaturedProducts,
        'title' => 'Featured Products',
        'payload' => [
            'ids' => [$earbuds->id, $jacket->id],
        ],
        'sort_order' => 4,
        'is_active' => true,
    ]);

    ContentBlock::create([
        'type' => ContentBlockType::FeaturedVendors,
        'title' => 'Top Vendors',
        'payload' => [
            'ids' => [$vendor->id],
        ],
        'sort_order' => 5,
        'is_active' => true,
    ]);

    ContentBlock::create([
        'type' => ContentBlockType::PromoGrid,
        'title' => 'Deals For You',
        'payload' => [
            'items' => [
                ['image' => 'content-blocks/promo-1.jpg', 'title' => 'Electronics Deals', 'link_url' => '/categories/electronics'],
                ['image' => 'content-blocks/promo-2.jpg', 'title' => 'Fashion Deals', 'link_url' => '/categories/fashion'],
            ],
        ],
        'sort_order' => 6,
        'is_active' => true,
    ]);

    // Inactive block must never render.
    ContentBlock::create([
        'type' => ContentBlockType::Banner,
        'title' => 'Should Not Appear',
        'payload' => ['image' => 'content-blocks/hidden.jpg', 'link_url' => '/hidden'],
        'sort_order' => 7,
        'is_active' => false,
    ]);

    $url = registerThemePreviewRoute();

    $response = $this->get($url);

    $response->assertOk();
    $response->assertSee('Big Season Sale');
    $response->assertSee('Shop Now');
    $response->assertSee('Electronics');
    $response->assertSee('Fashion');
    $response->assertSee('Wireless Bluetooth Earbuds');
    $response->assertSee('Women&#039;s Denim Jacket', false);
    $response->assertSee('Himalayan Bazaar');
    $response->assertSee('Electronics Deals');
    $response->assertSee('Fashion Deals');
    $response->assertDontSee('Should Not Appear');
});

test('theme colors reflect live admin edits without a rebuild', function () {
    $url = registerThemePreviewRoute();

    // Default (build-time / migration-default) color renders before any edit.
    $response = $this->get($url);
    $response->assertOk();
    $response->assertSee('--color-primary:#4f46e5', false);

    // Simulate an admin theme edit.
    SiteSetting::current()->update(['primary_color' => '#ff0000']);

    // Simulate the short cache TTL having elapsed (deliberately no
    // invalidation hook exists on SiteSetting itself).
    Cache::forget('site-settings');

    $response = $this->get($url);
    $response->assertOk();
    $response->assertSee('--color-primary:#ff0000', false);
});
