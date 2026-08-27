<?php

use App\Enums\BorderRadiusScale;
use App\Enums\ButtonStyle;
use App\Enums\ContentBlockType;
use App\Enums\LayoutDensity;
use App\Enums\ShadowStyle;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\VendorProfile;
use App\Services\Theme\ThemeSettingsService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Registers a throwaway route (not routes/web.php, which a later phase
 * owns) that renders the storefront layout with the home content-block
 * renderer plus a default (no explicit `variant`) `x-ui.button`, and
 * returns the URL to hit.
 */
function registerThemePreviewRoute(): string
{
    Route::middleware('web')->get('/__test-theme-preview', function () {
        return Blade::render(
            <<<'BLADE'
                <x-layouts.storefront>
                    <x-home-content-blocks />
                    <x-ui.button>Theme preview button</x-ui.button>
                </x-layouts.storefront>
                BLADE
        );
    });

    return '/__test-theme-preview';
}

/**
 * `CACHE_STORE=array` (see phpunit.xml) persists in-memory for the whole
 * test process — it is NOT reset between tests the way RefreshDatabase
 * resets the database. Every test in this file pokes the `site-settings`
 * cache key directly, so without this, a value one test writes (or an
 * intentionally invalid one, in the fallback test below) would leak into
 * whichever test runs next and silently change its SiteSetting values —
 * exactly the kind of cache bug the ThemeSettingsService caching pattern
 * itself was designed to guard against.
 */
afterEach(function (): void {
    Cache::forget('site-settings');
});

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

test('border radius, shadow, and layout density settings reflect live admin edits without a rebuild', function () {
    $url = registerThemePreviewRoute();

    // Defaults (matching what the storefront already looks like today)
    // render before any edit.
    $response = $this->get($url);
    $response->assertOk();
    $response->assertSee('--radius-sm:0.25rem;--radius-md:0.375rem;--radius-lg:0.5rem;', false);
    $response->assertSee('--shadow-sm:0 1px 2px 0 rgb(0 0 0 / 0.05);--shadow-lg:0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);', false);
    $response->assertSee('--card-padding:0.75rem;--section-spacing:2.5rem;', false);

    // Simulate an admin theme edit.
    SiteSetting::current()->update([
        'border_radius_scale' => BorderRadiusScale::Pill,
        'shadow_style' => ShadowStyle::Flat,
        'layout_density' => LayoutDensity::Spacious,
    ]);

    // Simulate the short cache TTL having elapsed.
    Cache::forget('site-settings');

    $response = $this->get($url);
    $response->assertOk();
    $response->assertSee('--radius-sm:9999px;--radius-md:9999px;--radius-lg:9999px;', false);
    $response->assertSee('--shadow-sm:none;--shadow-lg:none;', false);
    $response->assertSee('--card-padding:1.25rem;--section-spacing:4rem;', false);
});

test('button style setting changes the default x-ui.button variant classes without a rebuild', function () {
    $url = registerThemePreviewRoute();

    // Default ("solid") renders a filled primary button before any edit.
    $response = $this->get($url);
    $response->assertOk();
    $response->assertSee('bg-primary text-white hover:opacity-90', false);

    SiteSetting::current()->update(['button_style' => ButtonStyle::Outline]);
    Cache::forget('site-settings');

    $response = $this->get($url);
    $response->assertOk();
    $response->assertSee('bg-transparent text-primary border-2 border-primary', false);
    $response->assertDontSee('bg-primary text-white hover:opacity-90', false);

    SiteSetting::current()->update(['button_style' => ButtonStyle::Soft]);
    Cache::forget('site-settings');

    $response = $this->get($url);
    $response->assertOk();
    $response->assertSee('bg-primary/10 text-primary hover:bg-primary/20', false);
});

test('theme style settings fall back to sensible defaults when the stored value is missing or invalid', function () {
    // Bypass Eloquent's enum cast entirely (a raw SQL write, exactly how a
    // legacy row from before these columns existed — or a malformed manual
    // edit — could end up with a value that doesn't match any enum case).
    SiteSetting::current();
    DB::table('site_settings')->where('id', 1)->update([
        'border_radius_scale' => '',
        'shadow_style' => 'not-a-real-shadow-style',
        'layout_density' => '',
        'button_style' => 'not-a-real-button-style',
    ]);
    Cache::forget('site-settings');

    $service = app(ThemeSettingsService::class);

    expect($service->borderRadiusScale())->toBe(BorderRadiusScale::Rounded)
        ->and($service->shadowStyle())->toBe(ShadowStyle::Soft)
        ->and($service->layoutDensity())->toBe(LayoutDensity::Comfortable)
        ->and($service->buttonStyle())->toBe(ButtonStyle::Solid);

    $css = $service->cssVariables();
    expect($css)->toContain('--radius-sm:0.25rem;--radius-md:0.375rem;--radius-lg:0.5rem;')
        ->and($css)->toContain('--shadow-sm:0 1px 2px 0 rgb(0 0 0 / 0.05);')
        ->and($css)->toContain('--card-padding:0.75rem;--section-spacing:2.5rem;');
});
