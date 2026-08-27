<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\VendorSubOrderStatus;
use App\Livewire\Storefront\ProductCatalog;
use App\Livewire\Storefront\VendorShopPage;
use App\Livewire\Vendor\OrderManager;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/**
 * N+1 audit: renders each page twice with a different number of rows and
 * asserts the query count stays flat rather than scaling with the number
 * of products/orders shown. A flat-but-nonzero count is expected (the
 * eager-loaded relations still cost one query each); a count that grows
 * with the row count is the N+1 signature this guards against.
 */
function createPublishedProductWithReviews(VendorProfile $vendor, Category $category, int $reviewCount = 2): Product
{
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'category_id' => $category->id,
        'status' => ProductStatus::Published,
    ]);

    for ($i = 0; $i < $reviewCount; $i++) {
        $reviewer = User::factory()->create();

        Review::query()->create([
            'product_id' => $product->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
            'body' => 'Great product.',
            'is_visible' => true,
        ]);
    }

    return $product;
}

test('the product catalog does not issue more queries as more products are shown on the page', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $category = Category::factory()->create(['is_active' => true]);

    foreach (range(1, 3) as $i) {
        createPublishedProductWithReviews($vendor, $category);
    }

    DB::enableQueryLog();
    Livewire::test(ProductCatalog::class)->assertOk();
    $smallPageCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    foreach (range(1, 9) as $i) {
        createPublishedProductWithReviews($vendor, $category);
    }

    // 12 published products now -- a full page.
    DB::enableQueryLog();
    Livewire::test(ProductCatalog::class)->assertOk();
    $fullPageCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Not a strict equality: relation caching on a reused model instance
    // can shave a query off the second call, which would be a false
    // failure here. What actually matters -- and what an N+1 regression
    // would blow through -- is that the count does not grow with the
    // number of rows rendered.
    expect($fullPageCount)->toBeLessThanOrEqual($smallPageCount);
    expect($fullPageCount)->toBeLessThan(15);
});

test('the vendor shop page does not issue more queries as more products are shown on the page', function () {
    $vendor = VendorProfile::factory()->approved()->create(['shop_slug' => 'query-perf-shop']);
    $category = Category::factory()->create(['is_active' => true]);

    foreach (range(1, 3) as $i) {
        createPublishedProductWithReviews($vendor, $category);
    }

    DB::enableQueryLog();
    Livewire::test(VendorShopPage::class, ['slug' => 'query-perf-shop'])->assertOk();
    $smallPageCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    foreach (range(1, 9) as $i) {
        createPublishedProductWithReviews($vendor, $category);
    }

    DB::enableQueryLog();
    Livewire::test(VendorShopPage::class, ['slug' => 'query-perf-shop'])->assertOk();
    $fullPageCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Not a strict equality: relation caching on a reused model instance
    // can shave a query off the second call, which would be a false
    // failure here. What actually matters -- and what an N+1 regression
    // would blow through -- is that the count does not grow with the
    // number of rows rendered.
    expect($fullPageCount)->toBeLessThanOrEqual($smallPageCount);
    expect($fullPageCount)->toBeLessThan(15);
});

test('the vendor order manager does not issue more queries as more orders/items are shown on the page', function () {
    $this->seed(RoleSeeder::class);

    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $vendorUser = User::factory()->create();
    $vendorUser->assignRole('vendor');
    $vendor = VendorProfile::factory()->approved()->create(['user_id' => $vendorUser->id]);

    $makeOrderWithItems = function (int $itemCount) use ($customer, $vendor) {
        $order = Order::query()->create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $customer->id,
            'shipping_recipient_name' => 'Jane Doe',
            'shipping_phone' => '9800000000',
            'shipping_province' => 'Bagmati',
            'shipping_district' => 'Kathmandu',
            'shipping_municipality' => 'Kathmandu Metro',
            'shipping_ward_no' => '5',
            'shipping_street_address' => 'Baneshwor',
            'subtotal' => 500 * $itemCount,
            'shipping_total' => 100,
            'discount_total' => 0,
            'grand_total' => 500 * $itemCount + 100,
            'currency' => 'NPR',
            'payment_method' => PaymentMethod::Cod,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::Processing,
            'placed_at' => now(),
        ]);

        for ($i = 0; $i < $itemCount; $i++) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'vendor_id' => $vendor->id,
                'product_name_snapshot' => "Product {$i}",
                'unit_price_snapshot' => 500,
                'quantity' => 1,
                'line_total' => 500,
                'commission_percent_snapshot' => 10,
                'commission_amount' => 50,
                'vendor_earning' => 450,
                'vendor_status' => VendorSubOrderStatus::Pending,
            ]);
        }
    };

    $makeOrderWithItems(1);
    $makeOrderWithItems(2);

    DB::enableQueryLog();
    Livewire::actingAs($vendorUser)->test(OrderManager::class)->assertOk();
    $smallCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();

    foreach (range(1, 4) as $i) {
        $makeOrderWithItems(2);
    }

    // 6 orders now, each with multiple items.
    DB::enableQueryLog();
    Livewire::actingAs($vendorUser)->test(OrderManager::class)->assertOk();
    $largerCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // See the comment on the equivalent assertion in the ProductCatalog
    // test above -- relation caching on the reused $vendorUser instance
    // can make the second call cheaper, not equal; what matters is that
    // it never gets more expensive as more orders/items are rendered.
    expect($largerCount)->toBeLessThanOrEqual($smallCount);
    expect($largerCount)->toBeLessThan(15);
});
