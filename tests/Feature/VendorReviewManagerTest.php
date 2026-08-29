<?php

use App\Livewire\Vendor\ReviewManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->vendorUser = User::factory()->create();
    $this->vendorUser->assignRole('vendor');
    $this->vendorProfile = VendorProfile::factory()->approved()->create(['user_id' => $this->vendorUser->id]);

    $this->category = Category::factory()->create(['is_active' => true]);
    $this->product = Product::factory()->create([
        'vendor_id' => $this->vendorProfile->id,
        'category_id' => $this->category->id,
    ]);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

function createReview(array $attributes = []): Review
{
    return Review::query()->create(array_merge([
        'rating' => 4,
        'body' => 'A perfectly ordinary review.',
        'is_visible' => true,
    ], $attributes));
}

test('a vendor can reply to a review on their own product', function () {
    $review = createReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->vendorUser)
        ->test(ReviewManager::class)
        ->call('startReply', $review->id)
        ->set('replyBody', 'Thanks so much for the feedback!')
        ->call('saveReply', $review->id)
        ->assertDispatched('notify', message: 'Reply saved.', type: 'success');

    $review->refresh();

    expect($review->vendor_reply)->toBe('Thanks so much for the feedback!')
        ->and($review->vendor_replied_at)->not->toBeNull();
});

test('a vendor can edit an existing reply', function () {
    $review = createReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
        'vendor_reply' => 'Original reply.',
        'vendor_replied_at' => now(),
    ]);

    Livewire::actingAs($this->vendorUser)
        ->test(ReviewManager::class)
        ->call('startReply', $review->id)
        ->assertSet('replyBody', 'Original reply.')
        ->set('replyBody', 'Updated reply.')
        ->call('saveReply', $review->id);

    expect($review->fresh()->vendor_reply)->toBe('Updated reply.');
});

test('an empty reply fails validation', function () {
    $review = createReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
    ]);

    Livewire::actingAs($this->vendorUser)
        ->test(ReviewManager::class)
        ->call('startReply', $review->id)
        ->set('replyBody', '')
        ->call('saveReply', $review->id)
        ->assertHasErrors(['replyBody']);

    expect($review->fresh()->vendor_reply)->toBeNull();
});

test('a second vendor gets a 403 attempting to reply to another vendor\'s product review', function () {
    $review = createReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
    ]);

    $otherVendorUser = User::factory()->create();
    $otherVendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $otherVendorUser->id]);

    Livewire::actingAs($otherVendorUser)
        ->test(ReviewManager::class)
        ->call('saveReply', $review->id)
        ->assertForbidden();

    expect($review->fresh()->vendor_reply)->toBeNull();
});

test('a second vendor gets a 403 attempting to even open the reply form on another vendor\'s product review', function () {
    $review = createReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
    ]);

    $otherVendorUser = User::factory()->create();
    $otherVendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $otherVendorUser->id]);

    Livewire::actingAs($otherVendorUser)
        ->test(ReviewManager::class)
        ->call('startReply', $review->id)
        ->assertForbidden();
});

test('a vendor does not see a review admin has hidden, and cannot reply to it directly either', function () {
    $hiddenReview = createReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
        'body' => 'This review should stay hidden from the vendor list.',
        'is_visible' => false,
    ]);
    $visibleReview = createReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
        'body' => 'This review should be visible to the vendor.',
        'is_visible' => true,
    ]);

    $response = Livewire::actingAs($this->vendorUser)->test(ReviewManager::class);

    $response->assertDontSee('This review should stay hidden from the vendor list.');
    $response->assertSee('This review should be visible to the vendor.');

    // Direct component call against the hidden review's id is also rejected.
    Livewire::actingAs($this->vendorUser)
        ->test(ReviewManager::class)
        ->call('saveReply', $hiddenReview->id)
        ->assertForbidden();

    expect($hiddenReview->fresh()->vendor_reply)->toBeNull();
});

test('a vendor sees a reply already on their product review appear on the storefront product page', function () {
    createReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
        'vendor_reply' => 'We appreciate your business!',
        'vendor_replied_at' => now(),
    ]);

    $response = $this->get(route('products.show', $this->product->slug));

    $response->assertOk();
    $response->assertSee('We appreciate your business!');
});

test('guest, customer, and pending vendor are all redirected away from the vendor reviews route', function () {
    $this->get(route('vendor.reviews.index'))->assertRedirect(route('login'));

    $this->actingAs($this->customer)->get(route('vendor.reviews.index'))->assertRedirect(route('vendor.apply'));

    $pendingUser = User::factory()->create();
    $pendingUser->assignRole('vendor');
    VendorProfile::factory()->create(['user_id' => $pendingUser->id]);

    $this->actingAs($pendingUser)->get(route('vendor.reviews.index'))->assertRedirect(route('vendor.status'));
});

test('an approved vendor reaches the reviews route', function () {
    $this->actingAs($this->vendorUser)->get(route('vendor.reviews.index'))->assertOk();
});
