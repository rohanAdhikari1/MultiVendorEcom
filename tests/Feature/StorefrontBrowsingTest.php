<?php

use App\Enums\ContentBlockType;
use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Livewire\Storefront\ProductCatalog;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Livewire\Livewire;

test('the home page returns 200 and renders content blocks', function () {
    $vendor = VendorProfile::factory()->approved()->create(['shop_name' => 'Himalayan Bazaar']);
    $category = Category::factory()->create(['name' => 'Electronics']);
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Featured Earbuds',
        'is_featured' => true,
    ]);

    ContentBlock::create([
        'type' => ContentBlockType::Hero,
        'title' => 'Welcome to the Marketplace',
        'payload' => ['image' => 'content-blocks/hero.jpg', 'cta_text' => 'Shop Now', 'cta_url' => '/products'],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Welcome to the Marketplace');
});

test('the product catalog lists published products and supports category filtering', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $electronics = Category::factory()->create(['name' => 'Electronics', 'is_active' => true]);
    $fashion = Category::factory()->create(['name' => 'Fashion', 'is_active' => true]);

    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $electronics->id,
        'name' => 'Wireless Earbuds',
        'status' => ProductStatus::Published,
    ]);
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $fashion->id,
        'name' => 'Denim Jacket',
        'status' => ProductStatus::Published,
    ]);
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $electronics->id,
        'name' => 'Draft Gadget',
        'status' => ProductStatus::Draft,
    ]);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('Wireless Earbuds');
    $response->assertSee('Denim Jacket');
    $response->assertDontSee('Draft Gadget');

    // Category deep-link entry point.
    $response = $this->get(route('products.index', ['category' => $electronics->slug]));

    $response->assertOk();
    $response->assertSee('Wireless Earbuds');
    $response->assertDontSee('Denim Jacket');
});

test('the vendor shop page shows an approved vendor and their published products', function () {
    $vendor = VendorProfile::factory()->approved()->create([
        'shop_name' => 'Himalayan Bazaar',
        'shop_slug' => 'himalayan-bazaar',
    ]);
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Handwoven Wool Scarf',
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('store.show', 'himalayan-bazaar'));

    $response->assertOk();
    $response->assertSee('Himalayan Bazaar');
    $response->assertSee('Handwoven Wool Scarf');
});

test('a made-up vendor slug 404s', function () {
    $this->get(route('store.show', 'does-not-exist'))->assertNotFound();
});

test('a pending vendor shop 404s', function () {
    $vendor = VendorProfile::factory()->create([
        'shop_slug' => 'pending-shop',
        'status' => VendorApplicationStatus::Pending,
    ]);

    $this->get(route('store.show', 'pending-shop'))->assertNotFound();
});

test('the product detail page renders expected content for a published product', function () {
    $vendor = VendorProfile::factory()->approved()->create(['shop_name' => 'Himalayan Bazaar']);
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Portable Power Bank',
        'slug' => 'portable-power-bank-1',
        'description' => 'A reliable power bank for travel.',
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertSee('Portable Power Bank');
    $response->assertSee('A reliable power bank for travel.');
    $response->assertSee('Himalayan Bazaar');
});

test('a draft product detail page 404s', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->draft()->create([
        'vendor_id' => $vendor->id,
        'slug' => 'unlisted-draft-product',
    ]);

    $this->get(route('products.show', $product->slug))->assertNotFound();
});

test('an archived product detail page 404s rather than rendering', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->archived()->create([
        'vendor_id' => $vendor->id,
        'slug' => 'unlisted-archived-product',
    ]);

    $this->get(route('products.show', $product->slug))->assertNotFound();
});

test('an archived product does not appear in the catalog listing', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    Product::factory()->archived()->create(['vendor_id' => $vendor->id, 'name' => 'Archived Widget']);
    Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Live Widget', 'status' => ProductStatus::Published]);

    $response = $this->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('Live Widget');
    $response->assertDontSee('Archived Widget');
});

test('an out-of-stock product hides the add-to-cart action on its detail page but still shows the Out of Stock badge', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'stock_quantity' => 0,
        'slug' => 'out-of-stock-product',
        'status' => ProductStatus::Published,
    ]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertSee('Out of Stock');
    $response->assertDontSee('Add to Cart');
});

test('filtering by a category with zero products shows the empty state, not an error', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $categoryWithProducts = Category::factory()->create(['is_active' => true]);
    $emptyCategory = Category::factory()->create(['is_active' => true]);

    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $categoryWithProducts->id,
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.index', ['category' => $emptyCategory->slug]));

    $response->assertOk();
    $response->assertSee('No products found');
});

test('a vendor shop page with zero published products shows the empty state, not an error', function () {
    $vendor = VendorProfile::factory()->approved()->create(['shop_slug' => 'empty-shop']);

    $response = $this->get(route('store.show', 'empty-shop'));

    $response->assertOk();
    $response->assertSee('No products yet');
});

test('the last page of an exact-multiple product count renders the correct remainder with no error', function () {
    $vendor = VendorProfile::factory()->approved()->create();

    foreach (range(1, 24) as $i) {
        Product::factory()->create([
            'vendor_id' => $vendor->id,
            'status' => ProductStatus::Published,
            'name' => sprintf('Item %02d', $i),
            'price' => $i * 10,
        ]);
    }

    // 24 products / 12 per page = exactly 2 full pages.
    Livewire::test(ProductCatalog::class)
        ->set('sort', 'price_asc')
        ->call('gotoPage', 2)
        ->assertOk()
        ->assertSee('Item 13')
        ->assertSee('Item 24')
        ->assertDontSee('Item 12');

    // A page past the last one doesn't error either.
    Livewire::test(ProductCatalog::class)
        ->set('sort', 'price_asc')
        ->call('gotoPage', 3)
        ->assertOk();
});

test('a product name containing a script tag is rendered escaped, not executed, on the catalog and detail pages', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $malicious = '<script>alert(1)</script>';
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => $malicious,
        'description' => $malicious,
        'slug' => 'xss-test-product',
        'status' => ProductStatus::Published,
    ]);

    $catalogResponse = $this->get(route('products.index'));
    $catalogResponse->assertOk();
    $catalogResponse->assertSee($malicious); // assertSee escapes by default, so this proves the escaped form is present.
    $catalogResponse->assertDontSee($malicious, false); // raw, unescaped markup must not appear.

    $detailResponse = $this->get(route('products.show', $product->slug));
    $detailResponse->assertOk();
    $detailResponse->assertSee($malicious);
    $detailResponse->assertDontSee($malicious, false);
});
