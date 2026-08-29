<?php

use App\Enums\ContentBlockType;
use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Livewire\Storefront\ProductCatalog;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\Review;
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

test('searching by name matches, searching by description matches, and an unmatched term is case-insensitive and empty', function () {
    $vendor = VendorProfile::factory()->approved()->create();

    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Wireless Earbuds',
        'description' => 'Great for the gym.',
        'status' => ProductStatus::Published,
    ]);
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Leather Wallet',
        'description' => 'Handmade with premium earbud-grade stitching.',
        'status' => ProductStatus::Published,
    ]);
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Denim Jacket',
        'description' => 'Classic fit.',
        'status' => ProductStatus::Published,
    ]);

    // Matches by name.
    $response = $this->get(route('products.index', ['q' => 'Earbuds']));
    $response->assertOk();
    $response->assertSee('Wireless Earbuds');
    $response->assertDontSee('Denim Jacket');

    // Matches by description (product name itself does not contain the term).
    $response = $this->get(route('products.index', ['q' => 'earbud-grade']));
    $response->assertOk();
    $response->assertSee('Leather Wallet');
    $response->assertDontSee('Denim Jacket');

    // Case-insensitive.
    $response = $this->get(route('products.index', ['q' => 'WIRELESS EARBUDS']));
    $response->assertOk();
    $response->assertSee('Wireless Earbuds');

    // Unmatched term: zero results, correct empty state referencing the term.
    $response = $this->get(route('products.index', ['q' => 'nonexistent-term-xyz']));
    $response->assertOk();
    $response->assertSee('No products found for "nonexistent-term-xyz"');
    $response->assertDontSee('Wireless Earbuds');
});

test('search combines with other filters using AND semantics', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $electronics = Category::factory()->create(['name' => 'Electronics', 'is_active' => true]);
    $fashion = Category::factory()->create(['name' => 'Fashion', 'is_active' => true]);

    // Matches search + category + in stock: should appear.
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $electronics->id,
        'name' => 'Wireless Speaker',
        'stock_quantity' => 5,
        'status' => ProductStatus::Published,
    ]);

    // Matches search but wrong category: should not appear when category filter applied.
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $fashion->id,
        'name' => 'Wireless Beanie',
        'stock_quantity' => 5,
        'status' => ProductStatus::Published,
    ]);

    // Matches search + category but out of stock: should not appear when in-stock-only applied.
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $electronics->id,
        'name' => 'Wireless Charger',
        'stock_quantity' => 0,
        'status' => ProductStatus::Published,
    ]);

    Livewire::test(ProductCatalog::class)
        ->set('search', 'Wireless')
        ->set('categoryId', $electronics->id)
        ->set('inStockOnly', true)
        ->assertOk()
        ->assertSee('Wireless Speaker')
        ->assertDontSee('Wireless Beanie')
        ->assertDontSee('Wireless Charger');
});

test('the minimum rating filter narrows results using the already eager-loaded reviews average', function () {
    $vendor = VendorProfile::factory()->approved()->create();

    $highRated = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Five Star Product',
        'status' => ProductStatus::Published,
    ]);
    Review::query()->create([
        'product_id' => $highRated->id,
        'user_id' => User::factory()->create()->id,
        'rating' => 5,
        'body' => 'Excellent.',
        'is_visible' => true,
    ]);

    $lowRated = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Two Star Product',
        'status' => ProductStatus::Published,
    ]);
    Review::query()->create([
        'product_id' => $lowRated->id,
        'user_id' => User::factory()->create()->id,
        'rating' => 2,
        'body' => 'Not great.',
        'is_visible' => true,
    ]);

    Livewire::test(ProductCatalog::class)
        ->set('minRating', '4')
        ->assertOk()
        ->assertSee('Five Star Product')
        ->assertDontSee('Two Star Product');
});

test('the q query string deep-links a pre-filled search on mount', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Deep Link Gadget',
        'status' => ProductStatus::Published,
    ]);
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Unrelated Item',
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.index', ['q' => 'Deep Link']));

    $response->assertOk();
    $response->assertSee('Deep Link Gadget');
    $response->assertDontSee('Unrelated Item');
});

test('the header search form is a GET form pointing at the product catalog with a q field', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('action="'.route('products.index').'"', false);
    $response->assertSee('name="q"', false);
    $response->assertSee('method="GET"', false);
});

test('submitting the header search query string returns matching products from the catalog route', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Himalayan Salt Lamp',
        'status' => ProductStatus::Published,
    ]);
    Product::factory()->create([
        'vendor_id' => $vendor->id,
        'name' => 'Bamboo Cutting Board',
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get('/products?q=Himalayan+Salt');

    $response->assertOk();
    $response->assertSee('Himalayan Salt Lamp');
    $response->assertDontSee('Bamboo Cutting Board');
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
