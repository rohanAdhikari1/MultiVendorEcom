<?php

use App\Enums\ProductStatus;
use App\Livewire\Vendor\ProductManager\Form;
use App\Livewire\Vendor\ProductManager\Index;
use App\Models\Category;
use App\Models\Product;
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
});

test('a vendor can view their own product list', function () {
    Product::factory()->count(3)->create(['vendor_id' => $this->vendorProfile->id, 'category_id' => $this->category->id]);

    Livewire::actingAs($this->vendorUser)
        ->test(Index::class)
        ->assertOk()
        ->assertSee('Products');
});

test('a vendor can create a product without variants', function () {
    Livewire::actingAs($this->vendorUser)
        ->test(Form::class)
        ->set('name', 'Handwoven Basket')
        ->set('categoryId', $this->category->id)
        ->set('sku', 'BASK-001')
        ->set('price', '1200.00')
        ->set('compareAtPrice', '')
        ->set('stockQuantity', '25')
        ->set('status', 'published')
        ->call('save')
        ->assertRedirect(route('vendor.products.index'));

    $product = Product::query()->where('vendor_id', $this->vendorProfile->id)->firstOrFail();

    expect($product->name)->toBe('Handwoven Basket')
        ->and($product->slug)->toBe('handwoven-basket')
        ->and($product->status)->toBe(ProductStatus::Published)
        ->and((float) $product->price)->toBe(1200.0)
        ->and($product->stock_quantity)->toBe(25)
        ->and($product->is_featured)->toBeFalse()
        ->and($product->variants)->toHaveCount(0);
});

test('a vendor can create a product with a variant row', function () {
    Livewire::actingAs($this->vendorUser)
        ->test(Form::class)
        ->set('name', 'Cotton T-Shirt')
        ->set('categoryId', $this->category->id)
        ->set('price', '800.00')
        ->set('stockQuantity', '10')
        ->set('status', 'draft')
        ->call('addVariantRow')
        ->set('variantRows.0.size', 'M')
        ->set('variantRows.0.color', 'Blue')
        ->set('variantRows.0.stockQuantity', '5')
        ->call('save')
        ->assertRedirect(route('vendor.products.index'));

    $product = Product::query()->where('name', 'Cotton T-Shirt')->firstOrFail();

    expect($product->variants)->toHaveCount(1);
    expect($product->variants->first()->attributes)->toBe(['size' => 'M', 'color' => 'Blue']);
    expect($product->variants->first()->stock_quantity)->toBe(5);
});

test('a vendor can edit their own product', function () {
    $product = Product::factory()->create([
        'vendor_id' => $this->vendorProfile->id,
        'category_id' => $this->category->id,
        'name' => 'Old Name',
    ]);

    Livewire::actingAs($this->vendorUser)
        ->test(Form::class, ['product' => $product])
        ->assertSet('name', 'Old Name')
        ->set('name', 'New Name')
        ->call('save')
        ->assertRedirect(route('vendor.products.index'));

    expect($product->fresh()->name)->toBe('New Name');
});

test('a vendor can delete their own product', function () {
    $product = Product::factory()->create(['vendor_id' => $this->vendorProfile->id, 'category_id' => $this->category->id]);

    Livewire::actingAs($this->vendorUser)
        ->test(Index::class)
        ->call('delete', $product->id);

    expect(Product::query()->find($product->id))->toBeNull();
});

test('a second vendor gets a 403 attempting to edit another vendor\'s product', function () {
    $productOwner = $this->vendorProfile;
    $product = Product::factory()->create(['vendor_id' => $productOwner->id, 'category_id' => $this->category->id]);

    $otherVendorUser = User::factory()->create();
    $otherVendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $otherVendorUser->id]);

    Livewire::actingAs($otherVendorUser)
        ->test(Form::class, ['product' => $product])
        ->assertForbidden();
});

test('a second vendor gets a 403 attempting to delete another vendor\'s product', function () {
    $productOwner = $this->vendorProfile;
    $product = Product::factory()->create(['vendor_id' => $productOwner->id, 'category_id' => $this->category->id]);

    $otherVendorUser = User::factory()->create();
    $otherVendorUser->assignRole('vendor');
    VendorProfile::factory()->approved()->create(['user_id' => $otherVendorUser->id]);

    Livewire::actingAs($otherVendorUser)
        ->test(Index::class)
        ->call('delete', $product->id)
        ->assertForbidden();

    expect(Product::query()->find($product->id))->not->toBeNull();
});

test('a non-approved vendor cannot reach the product create form via the route', function () {
    $pendingUser = User::factory()->create();
    $pendingUser->assignRole('vendor');
    VendorProfile::factory()->create(['user_id' => $pendingUser->id]);

    $this->actingAs($pendingUser)->get(route('vendor.products.create'))->assertRedirect(route('vendor.status'));
});
