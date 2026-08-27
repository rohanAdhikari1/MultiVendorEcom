<?php

use App\Actions\Checkout\Exceptions\CouponNoLongerValidException;
use App\Actions\Checkout\PlaceOrderAction;
use App\Enums\PaymentMethod;
use App\Livewire\Storefront\Checkout;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
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

/**
 * Builds a customer with a 1000 NPR single-item cart and a saved address,
 * ready to exercise checkout+coupon flows against.
 */
function setUpCustomerWithCart(): array
{
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

    return [$user, $address];
}

test('applying a valid coupon reduces discount_total/grand_total and the math stays consistent', function () {
    [$user, $address] = setUpCustomerWithCart();

    $coupon = Coupon::factory()->percent(10)->create();

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('couponCode', $coupon->code)
        ->call('applyCoupon')
        ->assertHasNoErrors('couponCode')
        ->assertSet('appliedCouponId', $coupon->id)
        ->set('selectedAddressId', $address->id)
        ->set('paymentMethod', 'cod')
        ->call('placeOrder');

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    // subtotal 1000, shipping flat 100, 10% discount = 100.
    expect((float) $order->subtotal)->toBe(1000.0);
    expect((float) $order->shipping_total)->toBe(100.0);
    expect((float) $order->discount_total)->toBe(100.0);
    expect((float) $order->grand_total)->toBe(1000.0);
    expect((float) $order->grand_total)->toBe(
        round((float) $order->subtotal + (float) $order->shipping_total - (float) $order->discount_total, 2)
    );
});

test('applying an unknown coupon code shows an inline error and applies no discount', function () {
    [$user] = setUpCustomerWithCart();

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('couponCode', 'DOES-NOT-EXIST')
        ->call('applyCoupon')
        ->assertHasErrors(['couponCode'])
        ->assertSet('appliedCouponId', null);
});

test('applying an expired coupon code shows an inline error and applies no discount', function () {
    [$user] = setUpCustomerWithCart();

    $coupon = Coupon::factory()->expired()->create();

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('couponCode', $coupon->code)
        ->call('applyCoupon')
        ->assertHasErrors(['couponCode'])
        ->assertSet('appliedCouponId', null);
});

test('placing an order with an applied coupon increments used_count exactly once', function () {
    [$user, $address] = setUpCustomerWithCart();

    $coupon = Coupon::factory()->fixed(50)->create(['max_uses' => 1, 'used_count' => 0]);

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('couponCode', $coupon->code)
        ->call('applyCoupon')
        ->assertHasNoErrors('couponCode')
        ->set('selectedAddressId', $address->id)
        ->set('paymentMethod', 'cod')
        ->call('placeOrder');

    expect($coupon->fresh()->used_count)->toBe(1);
});

test('re-applying an already-exhausted single-use coupon on a fresh checkout is rejected', function () {
    $coupon = Coupon::factory()->fixed(50)->create(['max_uses' => 1, 'used_count' => 1]);

    [$user] = setUpCustomerWithCart();

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('couponCode', $coupon->code)
        ->call('applyCoupon')
        ->assertHasErrors(['couponCode'])
        ->assertSet('appliedCouponId', null);

    expect($coupon->fresh()->used_count)->toBe(1);
});

test('PlaceOrderAction rejects a coupon that becomes invalid between apply-time and place-time, and does not create an order', function () {
    // Simulates the race: the coupon was valid when the customer clicked
    // "Apply" (in-memory $coupon instance below), but by the time
    // PlaceOrderAction actually runs, another request has already
    // exhausted its single use -- the fresh DB read must catch this.
    [$user, $address] = setUpCustomerWithCart();

    $coupon = Coupon::factory()->fixed(50)->create(['max_uses' => 1, 'used_count' => 0]);
    $staleCoupon = $coupon->replicate();
    $staleCoupon->id = $coupon->id;

    // Another request consumes the coupon's only use first.
    Coupon::query()->whereKey($coupon->id)->increment('used_count');

    expect(fn () => app(PlaceOrderAction::class)->execute(
        $user->fresh(),
        $address,
        PaymentMethod::Cod,
        $staleCoupon,
    ))->toThrow(CouponNoLongerValidException::class);

    $this->assertDatabaseCount('orders', 0);
    // used_count was only incremented by the "other request" above, not
    // doubled by the rejected placement attempt.
    expect($coupon->fresh()->used_count)->toBe(1);
});

test('placing an order without a coupon is unaffected and discount_total stays zero', function () {
    [$user, $address] = setUpCustomerWithCart();

    Livewire::actingAs($user)
        ->test(Checkout::class)
        ->set('selectedAddressId', $address->id)
        ->set('paymentMethod', 'cod')
        ->call('placeOrder');

    $order = Order::query()->where('user_id', $user->id)->firstOrFail();

    expect((float) $order->discount_total)->toBe(0.0);
    expect((float) $order->grand_total)->toBe(1100.0);
});
