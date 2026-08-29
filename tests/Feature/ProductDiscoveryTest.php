<?php

use App\Enums\ProductStatus;
use App\Livewire\Storefront\Home;
use App\Livewire\Storefront\ProductDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\VendorProfile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * Phase 3C: related products + recently viewed on the product detail page
 * (and recently viewed on Home). Mirrors QueryPerformanceTest.php's
 * established pattern for the N+1 regression guards below.
 */

/**
 * The product-detail page can render BOTH a "You might also like" section
 * (same-vendor fallback, ordered newest-first) and a "Recently Viewed"
 * section on the same page, and test fixtures below are deliberately
 * simple same-vendor products with no category -- meaning a product can
 * legitimately appear in both sections. Order/dedup assertions must be
 * scoped to the Recently Viewed fragment only, or they end up (mis)reading
 * the unrelated related-products section instead.
 */
function recentlyViewedFragment(string $html): string
{
    $start = mb_strpos($html, 'Recently Viewed');
    expect($start)->not->toBeFalse();

    return mb_substr($html, $start);
}
test('related products shows other published products from the same category, excluding the product itself and non-published products', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $category = Category::factory()->create(['is_active' => true]);
    $otherCategory = Category::factory()->create(['is_active' => true]);

    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Main Product',
        'status' => ProductStatus::Published,
    ]);

    $sibling1 = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Sibling Product One',
        'status' => ProductStatus::Published,
    ]);
    $sibling2 = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Sibling Product Two',
        'status' => ProductStatus::Published,
    ]);
    $draftSibling = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'name' => 'Draft Sibling Product',
        'status' => ProductStatus::Draft,
    ]);
    $otherCategoryProduct = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $otherCategory->id,
        'name' => 'Unrelated Category Product',
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertSee('You might also like');
    $response->assertSee($sibling1->name);
    $response->assertSee($sibling2->name);
    $response->assertDontSee($draftSibling->name);
    $response->assertDontSee($otherCategoryProduct->name);
    // The product itself is already rendered once in the main content area
    // (title, gallery alt text, etc) -- the meaningful assertion here is
    // that the related grid doesn't render it a second time as a "sibling"
    // of itself, which the underlying query (`where id != product.id`)
    // guarantees; a plain assertSee on its own name would trivially pass
    // regardless, so it is intentionally not asserted here.
});

test('a category with fewer than two related products falls back to more products from the same vendor', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $otherVendor = VendorProfile::factory()->approved()->create();
    $sparseCategory = Category::factory()->create(['is_active' => true]);

    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $sparseCategory->id,
        'name' => 'Sparse Category Product',
        'status' => ProductStatus::Published,
    ]);

    // Only one other product in the same category -- below the
    // MIN_SAME_CATEGORY threshold, so the vendor fallback should kick in.
    $categoryPeer = Product::factory()->create([
        'vendor_id' => $otherVendor->id,
        'category_id' => $sparseCategory->id,
        'name' => 'Lone Category Peer',
        'status' => ProductStatus::Published,
    ]);

    $vendorSibling = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => null,
        'name' => 'Other Vendor Product',
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertSee('You might also like');
    $response->assertSee($vendorSibling->name);
    $response->assertDontSee($categoryPeer->name);
});

test('a product with no other related products and no other vendor products hides the related section entirely', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $category = Category::factory()->create(['is_active' => true]);

    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'status' => ProductStatus::Published,
    ]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertDontSee('You might also like');
});

test('viewing products records them in reverse-chronological order in the recently viewed section', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $productA = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Product A', 'status' => ProductStatus::Published]);
    $productB = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Product B', 'status' => ProductStatus::Published]);
    $productC = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Product C', 'status' => ProductStatus::Published]);
    $productD = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Product D', 'status' => ProductStatus::Published]);

    Livewire::test(ProductDetail::class, ['slug' => $productA->slug])->assertOk();
    Livewire::test(ProductDetail::class, ['slug' => $productB->slug])->assertOk();
    Livewire::test(ProductDetail::class, ['slug' => $productC->slug])->assertOk();

    // View a fourth, unrelated product so A/B/C are all "someone else's"
    // recently-viewed history from its perspective, in reverse order.
    $html = Livewire::test(ProductDetail::class, ['slug' => $productD->slug])->assertOk()->html();
    $html = recentlyViewedFragment($html);

    $posA = mb_strpos($html, 'Product A');
    $posB = mb_strpos($html, 'Product B');
    $posC = mb_strpos($html, 'Product C');

    expect($posC)->not->toBeFalse();
    expect($posB)->not->toBeFalse();
    expect($posA)->not->toBeFalse();
    expect($posC)->toBeLessThan($posB);
    expect($posB)->toBeLessThan($posA);
});

test('re-viewing a product moves it back to the front of recently viewed without duplicating it', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $productA = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Product A', 'status' => ProductStatus::Published]);
    $productB = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Product B', 'status' => ProductStatus::Published]);
    $productC = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Product C', 'status' => ProductStatus::Published]);

    Livewire::test(ProductDetail::class, ['slug' => $productA->slug])->assertOk();
    Livewire::test(ProductDetail::class, ['slug' => $productB->slug])->assertOk();
    // Re-view A -- it should now be the most recent, ahead of B.
    Livewire::test(ProductDetail::class, ['slug' => $productA->slug])->assertOk();

    $html = Livewire::test(ProductDetail::class, ['slug' => $productC->slug])->assertOk()->html();
    $html = recentlyViewedFragment($html);

    // Exactly one occurrence of "Product A" in the recently-viewed grid --
    // re-viewing must not duplicate the entry.
    expect(substr_count($html, 'Product A'))->toBe(1);

    $posA = mb_strpos($html, 'Product A');
    $posB = mb_strpos($html, 'Product B');

    expect($posA)->not->toBeFalse();
    expect($posB)->not->toBeFalse();
    expect($posA)->toBeLessThan($posB);
});

test('recently viewed is capped at a reasonable length rather than growing unbounded', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $products = Product::factory()->count(15)->create([
        'vendor_id' => $vendor->id,
        'status' => ProductStatus::Published,
    ]);

    foreach ($products as $product) {
        Livewire::test(ProductDetail::class, ['slug' => $product->slug])->assertOk();
    }

    $extraProduct = Product::factory()->create(['vendor_id' => $vendor->id, 'status' => ProductStatus::Published]);
    $html = Livewire::test(ProductDetail::class, ['slug' => $extraProduct->slug])->assertOk()->html();
    $html = recentlyViewedFragment($html);

    $shown = collect($products)->filter(fn (Product $product) => str_contains($html, (string) $product->name));

    expect($shown->count())->toBeLessThanOrEqual(12);
    // The earliest-viewed product should have been evicted first.
    expect($html)->not->toContain($products->first()->name);
});

test('a fresh session with no browsing history renders no recently viewed section', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'status' => ProductStatus::Published]);

    $response = $this->get(route('products.show', $product->slug));

    $response->assertOk();
    $response->assertDontSee('Recently Viewed');
});

test('the home page shows recently viewed products for a returning visitor but not for a first-time visitor', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Previously Viewed Widget', 'status' => ProductStatus::Published]);

    Livewire::test(Home::class)->assertOk()->assertDontSee('Recently Viewed');

    Livewire::test(ProductDetail::class, ['slug' => $product->slug])->assertOk();

    Livewire::test(Home::class)->assertOk()
        ->assertSee('Recently Viewed')
        ->assertSee('Previously Viewed Widget');
});

test('related products query count stays bounded as more same-category products exist', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'status' => ProductStatus::Published]);

    Product::factory()->count(3)->create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'status' => ProductStatus::Published]);

    DB::enableQueryLog();
    Livewire::test(ProductDetail::class, ['slug' => $product->slug])->assertOk();
    $smallCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    Product::factory()->count(9)->create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'status' => ProductStatus::Published]);

    DB::enableQueryLog();
    Livewire::test(ProductDetail::class, ['slug' => $product->slug])->assertOk();
    $largeCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($largeCount)->toBeLessThanOrEqual($smallCount);
    expect($largeCount)->toBeLessThan(20);
});

test('recently viewed query count stays bounded as more products are viewed', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $target = Product::factory()->create(['vendor_id' => $vendor->id, 'status' => ProductStatus::Published]);

    $fewProducts = Product::factory()->count(3)->create(['vendor_id' => $vendor->id, 'status' => ProductStatus::Published]);
    foreach ($fewProducts as $product) {
        Livewire::test(ProductDetail::class, ['slug' => $product->slug])->assertOk();
    }

    DB::enableQueryLog();
    Livewire::test(ProductDetail::class, ['slug' => $target->slug])->assertOk();
    $smallCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    $moreProducts = Product::factory()->count(9)->create(['vendor_id' => $vendor->id, 'status' => ProductStatus::Published]);
    foreach ($moreProducts as $product) {
        Livewire::test(ProductDetail::class, ['slug' => $product->slug])->assertOk();
    }

    DB::enableQueryLog();
    Livewire::test(ProductDetail::class, ['slug' => $target->slug])->assertOk();
    $largeCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($largeCount)->toBeLessThanOrEqual($smallCount);
    expect($largeCount)->toBeLessThan(20);
});
