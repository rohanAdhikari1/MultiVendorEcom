<?php

use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->refresh();

    $vendor = VendorProfile::factory()->approved()->create();
    $this->product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
});

function createModeratedReview(array $attributes = []): Review
{
    return Review::query()->create(array_merge([
        'rating' => 4,
        'body' => 'A perfectly ordinary review.',
        'is_visible' => true,
    ], $attributes));
}

it('loads the reviews list for an admin', function (): void {
    $this->actingAs($this->admin)->get('/admin/reviews')->assertOk();
});

it('lets an admin toggle is_visible on a review via the Filament resource', function (): void {
    $review = createModeratedReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
        'is_visible' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditReview::class, ['record' => $review->getRouteKey()])
        ->fillForm(['is_visible' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($review->fresh()->is_visible)->toBeFalse();
});

it('does not let an admin edit review content, only moderation fields', function (): void {
    $review = createModeratedReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
        'body' => 'Original review body.',
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditReview::class, ['record' => $review->getRouteKey()])
        ->assertFormFieldIsDisabled('body')
        ->assertFormFieldIsDisabled('rating')
        ->assertFormFieldIsDisabled('vendor_reply');
});

it('hides a review from the storefront product page once an admin toggles it invisible', function (): void {
    $review = createModeratedReview([
        'product_id' => $this->product->id,
        'user_id' => $this->customer->id,
        'body' => 'A review that will be hidden by moderation.',
        'is_visible' => true,
    ]);

    $this->get(route('products.show', $this->product->slug))->assertSee('A review that will be hidden by moderation.');

    Livewire::actingAs($this->admin)
        ->test(EditReview::class, ['record' => $review->getRouteKey()])
        ->fillForm(['is_visible' => false])
        ->call('save');

    $this->get(route('products.show', $this->product->slug))->assertDontSee('A review that will be hidden by moderation.');
});

it('blocks a non-admin from the reviews resource', function (): void {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $customer->refresh();

    $this->actingAs($customer)->get('/admin/reviews')->assertForbidden();
});
