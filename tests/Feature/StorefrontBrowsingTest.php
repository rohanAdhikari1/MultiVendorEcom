<?php

use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\VendorProfile;
use App\Enums\ContentBlockType;

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
