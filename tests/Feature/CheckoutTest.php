<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Storefront\Checkout;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\District;
use App\Models\Order;
use App\Models\Product;
use App\Models\Province;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\NepalLocationSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SiteSettingSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(NepalLocationSeeder::class);
    $this->seed(SiteSettingSeeder::class);
});

test('a guest is redirected to login when visiting checkout', function () {
    $this->get(route('checkout'))->assertRedirect(route('login'));
});

test('a user with an empty cart is redirected back to the cart page', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)->get(route('checkout'))->assertRedirect(route('cart'));
});

test('saving a new address requires the district to belong to the selected province', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 1000]);

    $cart = Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price_snapshot' => $product->price,
    ]);

    $province = Province::query()->first();
    $wrongDistrict = District::query()->where('province_id', '!=', $province->id)->first();

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('newRecipientName', 'Jane Doe')
        ->set('newPhone', '9800000000')
        ->set('newProvinceId', $province->id)
        ->set('newDistrictId', $wrongDistrict->id)
        ->set('newMunicipality', 'Kathmandu Metro')
        ->call('saveAddress')
        ->assertHasErrors(['newDistrictId']);

    $this->assertDatabaseCount('addresses', 0);
});

test('saving a valid new address succeeds and selects it', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 1000]);

    $cart = Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price_snapshot' => $product->price,
    ]);

    $province = Province::query()->first();
    $district = District::query()->where('province_id', $province->id)->first();

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('newRecipientName', 'Jane Doe')
        ->set('newPhone', '9800000000')
        ->set('newProvinceId', $province->id)
        ->set('newDistrictId', $district->id)
        ->set('newMunicipality', 'Kathmandu Metro')
        ->set('newWardNo', '5')
        ->set('newStreetAddress', 'Baneshwor')
        ->call('saveAddress')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'recipient_name' => 'Jane Doe',
        'province_id' => $province->id,
        'district_id' => $district->id,
        'is_default' => 1,
    ]);
});

test('placing a COD order creates the order and items with correct totals, commission math, clears the cart, and lands in Processing', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    // Vendor A has an explicit commission override.
    $vendorA = VendorProfile::factory()->approved()->create(['commission_override_percent' => 20]);
    $productA = Product::factory()->create(['vendor_id' => $vendorA->id, 'price' => 1000]);

    // Vendor B has no override, so it falls back to SiteSetting::default_commission_percent (10.00 seeded default).
    $vendorB = VendorProfile::factory()->approved()->create(['commission_override_percent' => null]);
    $productB = Product::factory()->create(['vendor_id' => $vendorB->id, 'price' => 500]);

    $cart = Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $productA->id,
        'vendor_id' => $vendorA->id,
        'quantity' => 1,
        'price_snapshot' => $productA->price,
    ]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $productB->id,
        'vendor_id' => $vendorB->id,
        'quantity' => 1,
        'price_snapshot' => $productB->price,
    ]);

    $province = Province::query()->first();
    $district = District::query()->where('province_id', $province->id)->first();
    $address = Address::query()->create([
        'user_id' => $user->id,
        'recipient_name' => 'Jane Doe',
        'phone' => '9800000000',
        'province_id' => $province->id,
        'district_id' => $district->id,
        'municipality' => 'Kathmandu Metro',
        'ward_no' => '5',
        'street_address' => 'Baneshwor',
        'is_default' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('selectedAddressId', $address->id)
        ->set('paymentMethod', 'cod')
        ->call('placeOrder');

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    // subtotal = 1000 + 500 = 1500; flat shipping = 100; grand total = 1600.
    expect((float) $order->subtotal)->toBe(1500.0);
    expect((float) $order->shipping_total)->toBe(100.0);
    expect((float) $order->discount_total)->toBe(0.0);
    expect((float) $order->grand_total)->toBe(1600.0);
    expect($order->status)->toBe(OrderStatus::Processing);
    expect($order->payment_status)->toBe(PaymentStatus::Pending);
    expect($order->shipping_recipient_name)->toBe('Jane Doe');
    expect($order->shipping_province)->toBe($province->name);
    expect($order->shipping_district)->toBe($district->name);

    $itemA = $order->items()->where('vendor_id', $vendorA->id)->firstOrFail();
    expect((float) $itemA->commission_percent_snapshot)->toBe(20.0);
    expect((float) $itemA->commission_amount)->toBe(200.0);
    expect((float) $itemA->vendor_earning)->toBe(800.0);

    $itemB = $order->items()->where('vendor_id', $vendorB->id)->firstOrFail();
    expect((float) $itemB->commission_percent_snapshot)->toBe(10.0);
    expect((float) $itemB->commission_amount)->toBe(50.0);
    expect((float) $itemB->vendor_earning)->toBe(450.0);

    // Cart is cleared.
    $this->assertDatabaseCount('cart_items', 0);
});

test('placing an order with an unselected address shows a validation error and does not create an order', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 1000]);

    $cart = Cart::query()->create(['user_id' => $user->id]);
    CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'vendor_id' => $vendor->id,
        'quantity' => 1,
        'price_snapshot' => $product->price,
    ]);

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('selectedAddressId', null)
        ->set('paymentMethod', 'cod')
        ->call('placeOrder')
        ->assertHasErrors(['selectedAddressId']);

    $this->assertDatabaseCount('orders', 0);
});
