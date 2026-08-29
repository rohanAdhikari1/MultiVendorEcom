<?php

use App\Livewire\Shared\WishlistButton;
use App\Livewire\Storefront\Wishlist;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use App\Models\WishlistItem;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('toggling the wishlist button for an authenticated user creates a wishlist item', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::actingAs($user)
        ->test(WishlistButton::class, ['product' => $product])
        ->call('toggle')
        ->assertSet('isWishlisted', true);

    $this->assertDatabaseHas('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

test('toggling the wishlist button again removes the wishlist item', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $component = Livewire::actingAs($user)->test(WishlistButton::class, ['product' => $product]);

    $component->call('toggle')->assertSet('isWishlisted', true);
    $component->call('toggle')->assertSet('isWishlisted', false);

    $this->assertDatabaseMissing('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
    $this->assertDatabaseCount('wishlist_items', 0);
});

test('toggling repeatedly never duplicates a wishlist row and never errors on the unique constraint', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $component = Livewire::actingAs($user)->test(WishlistButton::class, ['product' => $product]);

    // add, remove, add, remove -- across four toggles the table should
    // never carry more than one row for this user+product.
    $component->call('toggle');
    $component->call('toggle');
    $component->call('toggle');
    $component->call('toggle');

    $this->assertDatabaseCount('wishlist_items', 0);
});

test('a guest cannot wishlist a product', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::test(WishlistButton::class, ['product' => $product])->call('toggle');

    $this->assertDatabaseCount('wishlist_items', 0);
});

test('the guest wishlist button renders a login link instead of the toggle action', function () {
    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::test(WishlistButton::class, ['product' => $product])
        ->assertSeeHtml('Log in to add to wishlist')
        ->assertDontSeeHtml('wire:click.stop.prevent="toggle"');
});

test('toggling the wishlist dispatches wishlist-updated and a success notify toast', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::actingAs($user)
        ->test(WishlistButton::class, ['product' => $product])
        ->call('toggle')
        ->assertDispatched('wishlist-updated')
        ->assertDispatched('notify', message: 'Added to wishlist.', type: 'success');
});

test('removing dispatches a removed notify toast', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $component = Livewire::actingAs($user)->test(WishlistButton::class, ['product' => $product]);
    $component->call('toggle');

    $component->call('toggle')
        ->assertDispatched('notify', message: 'Removed from wishlist.', type: 'success');
});

test('a guest is redirected to login when visiting the wishlist page', function () {
    $this->get(route('wishlist'))->assertRedirect(route('login'));
});

test('the wishlist page shows only the authenticated user\'s own wishlisted products', function () {
    $userA = User::factory()->create();
    $userA->assignRole('customer');
    $userB = User::factory()->create();
    $userB->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $productA = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Trekking Poles']);
    $productB = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Yak Wool Blanket']);

    WishlistItem::query()->create(['user_id' => $userA->id, 'product_id' => $productA->id]);
    WishlistItem::query()->create(['user_id' => $userB->id, 'product_id' => $productB->id]);

    Livewire::actingAs($userA)
        ->test(Wishlist::class)
        ->assertSee('Trekking Poles')
        ->assertDontSee('Yak Wool Blanket');
});

test('an empty wishlist shows the empty state rather than erroring', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    Livewire::actingAs($user)
        ->test(Wishlist::class)
        ->assertOk()
        ->assertSee('Your wishlist is empty');
});

test('removing a product from the wishlist on the wishlist page live-updates the grid', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $vendor = VendorProfile::factory()->approved()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Prayer Flags']);

    WishlistItem::query()->create(['user_id' => $user->id, 'product_id' => $product->id]);

    Livewire::actingAs($user)
        ->test(Wishlist::class)
        ->assertSee('Prayer Flags')
        ->call('refresh')
        ->assertSee('Prayer Flags');

    WishlistItem::query()->where('user_id', $user->id)->where('product_id', $product->id)->delete();

    Livewire::actingAs($user)
        ->test(Wishlist::class)
        ->assertDontSee('Prayer Flags')
        ->assertSee('Your wishlist is empty');
});
