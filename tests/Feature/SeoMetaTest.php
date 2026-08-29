<?php

use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * `CACHE_STORE=array` (see phpunit.xml) is not reset between tests the way
 * RefreshDatabase resets the database -- every test here that touches
 * SiteSetting directly needs this, matching the pattern already established
 * in ThemeRenderingTest.php.
 *
 * beforeEach also warms the `site_settings` row via `SiteSetting::current()`
 * before any page render does: on a genuinely fresh table, `current()`'s
 * `firstOrCreate()` returns the freshly-inserted in-memory model as-is,
 * which does NOT reflect column defaults applied only at the DB level (e.g.
 * `site_name`'s default `'Marketplace'`) until the row is re-fetched. Left
 * alone, the *first* call anywhere -- which in a real request is
 * ThemeSettingsService's cached read -- would cache an empty site name for
 * the rest of the test. Calling `current()` here first means that read
 * happens on an existing row instead, fetching the real persisted default.
 */
beforeEach(function (): void {
    Cache::forget('site-settings');
    SiteSetting::current();
});

afterEach(function (): void {
    Cache::forget('site-settings');
});

test('the product detail page renders a product-specific title, description, and og tags', function () {
    Storage::fake('public');

    $vendor = VendorProfile::factory()->approved()->create(['shop_name' => 'Himalayan Bazaar']);
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Portable Power Bank',
        'slug' => 'portable-power-bank-og',
        'description' => 'A reliable, fast-charging power bank built for long trips across Nepal.',
        'status' => ProductStatus::Published,
    ]);
    ProductImage::create([
        'product_id' => $product->id,
        'path' => 'products/power-bank.jpg',
        'sort_order' => 0,
        'is_primary' => true,
    ]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertSee('<title>Portable Power Bank - Marketplace</title>', false);
    $response->assertSee('<meta property="og:type" content="product">', false);
    $response->assertSee('<meta property="og:title" content="Portable Power Bank - Marketplace">', false);
    $response->assertSee('A reliable, fast-charging power bank built for long trips across Nepal.', false);
    $response->assertSee(Storage::disk('public')->url('products/power-bank.jpg'), false);
});

test('a product with no image falls back to the site logo for og:image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('branding/logo.png', 'fake-logo-bytes');
    SiteSetting::current()->update(['logo_path' => 'branding/logo.png']);

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'slug' => 'no-image-product',
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertSee('<meta property="og:image" content="'.Storage::disk('public')->url('branding/logo.png').'">', false);
});

test('a product with no description falls back to a non-empty site-wide description with no broken meta tag', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'slug' => 'no-description-product',
        'description' => null,
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertSee('<meta name="description" content="', false);
    $response->assertDontSee('<meta name="description" content="">', false);
});

test('a product description containing HTML is stripped before truncation, not cut mid-tag', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $htmlDescription = '<p>This power bank is <strong>fast-charging</strong> and built for long trips. '
        .str_repeat('Extra padding text to push well past the truncation limit. ', 5)
        .'</p><script>alert(1)</script>';

    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'slug' => 'html-description-product',
        'description' => $htmlDescription,
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $content = $response->getContent();

    // Pull out the rendered meta description's content attribute value.
    preg_match('/<meta name="description" content="([^"]*)">/', (string) $content, $matches);
    $renderedDescription = $matches[1] ?? null;

    expect($renderedDescription)->not->toBeNull();
    expect($renderedDescription)->not->toContain('<');
    expect($renderedDescription)->not->toContain('>');
    expect($renderedDescription)->not->toContain('script');
    expect(strlen((string) $renderedDescription))->toBeLessThanOrEqual(165);
});

test('the vendor shop page renders a vendor-specific title, description, and og:image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('vendors/logo.png', 'fake-logo-bytes');

    $vendor = VendorProfile::factory()->approved()->create([
        'shop_name' => 'Himalayan Bazaar',
        'shop_slug' => 'himalayan-bazaar-og',
        'description' => 'Curated goods from the heart of the Himalayas.',
        'logo_path' => 'vendors/logo.png',
    ]);

    $response = $this->get(route('store.show', $vendor->shop_slug));

    $response->assertOk();
    $response->assertSee('<title>Himalayan Bazaar - Marketplace</title>', false);
    $response->assertSee('Curated goods from the heart of the Himalayas.', false);
    $response->assertSee('<meta property="og:image" content="'.Storage::disk('public')->url('vendors/logo.png').'">', false);
});

test('a vendor with no description or logo falls back sensibly with no broken meta tag', function () {
    $vendor = VendorProfile::factory()->approved()->create([
        'shop_name' => 'Bare Bones Shop',
        'shop_slug' => 'bare-bones-shop',
        'description' => null,
        'logo_path' => null,
        'banner_path' => null,
    ]);

    $response = $this->get(route('store.show', $vendor->shop_slug));

    $response->assertOk();
    $response->assertSee('<title>Bare Bones Shop - Marketplace</title>', false);
    $response->assertSee('<meta name="description" content="', false);
    $response->assertDontSee('<meta name="description" content="">', false);
});

test('the home page renders the site tagline as its title and a marketplace description', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('<title>Shop Trusted Local Vendors in Nepal - Marketplace</title>', false);
    $response->assertSee('<meta property="og:type" content="website">', false);
    $response->assertSee('Discover quality products from trusted local vendors across Nepal.', false);
});

test('the product catalog title reflects the active category filter', function () {
    $category = Category::factory()->create(['name' => 'Electronics', 'is_active' => true]);

    $response = $this->get(route('products.index', ['category' => $category->slug]));

    $response->assertOk();
    $response->assertSee('<title>Electronics - Products - Marketplace</title>', false);
});

test('the product catalog title is generic with no active filter', function () {
    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('<title>Products - Marketplace</title>', false);
});

test('the cart page gets its own title with no leftover product meta from a previous page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('cart'));

    $response->assertOk();
    $response->assertSee('<title>Cart - Marketplace</title>', false);
    $response->assertSee('<meta property="og:type" content="website">', false);
});

test('the vendor dashboard sets a noindex robots meta tag', function () {
    $this->seed(RoleSeeder::class);

    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);

    $response = $this->actingAs($vendorUser)->get(route('vendor.dashboard'));

    $response->assertOk();
    $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('sitemap.xml is valid XML and includes only publicly visible products, vendors, and categories', function () {
    $approvedVendor = VendorProfile::factory()->approved()->create(['shop_slug' => 'sitemap-approved-vendor']);
    $pendingVendor = VendorProfile::factory()->create([
        'shop_slug' => 'sitemap-pending-vendor',
        'status' => VendorApplicationStatus::Pending,
    ]);
    $rejectedVendor = VendorProfile::factory()->create([
        'shop_slug' => 'sitemap-rejected-vendor',
        'status' => VendorApplicationStatus::Rejected,
    ]);

    $category = Category::factory()->create(['slug' => 'sitemap-category', 'is_active' => true]);

    $publishedProduct = Product::factory()->create([
        'vendor_id' => $approvedVendor->id,
        'category_id' => $category->id,
        'slug' => 'sitemap-published-product',
        'status' => ProductStatus::Published,
    ]);
    $draftProduct = Product::factory()->create([
        'vendor_id' => $approvedVendor->id,
        'slug' => 'sitemap-draft-product',
        'status' => ProductStatus::Draft,
    ]);
    $archivedProduct = Product::factory()->create([
        'vendor_id' => $approvedVendor->id,
        'slug' => 'sitemap-archived-product',
        'status' => ProductStatus::Archived,
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml');

    $body = $response->getContent();
    $xml = new SimpleXMLElement((string) $body);
    expect($xml->getName())->toBe('urlset');

    // `$xml->url` (property access) collapses every sibling `<url>` element
    // into a single entry keyed by tag name -- iterating `children()` with
    // `iterator_to_array(..., false)` (drop keys) is what actually returns
    // each one as a distinct array element.
    $locs = collect(iterator_to_array($xml->children(), false))
        ->map(fn ($url) => (string) $url->loc)
        ->all();

    expect($locs)->toContain(route('home'));
    expect($locs)->toContain(route('products.index'));
    expect($locs)->toContain(route('products.show', $publishedProduct->slug));
    expect($locs)->toContain(route('store.show', $approvedVendor->shop_slug));
    expect($locs)->toContain(route('products.index', ['category' => $category->slug]));

    expect($locs)->not->toContain(route('products.show', $draftProduct->slug));
    expect($locs)->not->toContain(route('products.show', $archivedProduct->slug));
    expect($locs)->not->toContain(route('store.show', $pendingVendor->shop_slug));
    expect($locs)->not->toContain(route('store.show', $rejectedVendor->shop_slug));
});

test('robots.txt disallows the private storefront and back-office areas and references the sitemap', function () {
    // public/robots.txt is a static asset served directly by the web
    // server / PHP's built-in router (see the real-server smoke test),
    // never by Laravel's own routing -- Pest's in-process test client has
    // no route for it, so its content is verified by reading the file
    // straight off disk here.
    $contents = file_get_contents(public_path('robots.txt'));

    expect($contents)->not->toBeFalse();
    expect($contents)->toContain('Disallow: /vendor/');
    expect($contents)->toContain('Disallow: /admin/');
    expect($contents)->toContain('Disallow: /cart');
    expect($contents)->toContain('Disallow: /checkout');
    expect($contents)->toContain('Disallow: /orders');
    expect($contents)->toContain('Sitemap:');
    expect($contents)->toContain('/sitemap.xml');
});
