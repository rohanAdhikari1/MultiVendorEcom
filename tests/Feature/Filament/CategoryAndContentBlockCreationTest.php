<?php

use App\Enums\ContentBlockType;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\ContentBlocks\Pages\CreateContentBlock;
use App\Models\Category;
use App\Models\ContentBlock;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // `is_active` is excluded from User::$fillable, so the in-memory model
    // needs a refresh before being handed to actingAs()/Livewire::actingAs()
    // — see AdminResourceAccessTest for the full explanation.
    $this->admin->refresh();
});

it('creates a category from the admin panel', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateCategory::class)
        ->fillForm([
            'name' => 'Sports & Outdoors',
            'slug' => 'sports-outdoors',
            'is_active' => true,
            'sort_order' => 5,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = Category::query()->where('slug', 'sports-outdoors')->first();

    expect($category)->not->toBeNull()
        ->and($category->name)->toBe('Sports & Outdoors')
        ->and($category->is_active)->toBeTrue()
        ->and($category->sort_order)->toBe(5);
});

it('creates a hero content block with the payload packed from type-specific fields', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateContentBlock::class)
        ->fillForm([
            'type' => ContentBlockType::Hero->value,
            'title' => 'Homepage hero',
            'subtitle' => 'Welcome to the marketplace',
            'is_active' => true,
            'sort_order' => 1,
            'hero_cta_text' => 'Shop now',
            'hero_cta_url' => 'https://example.com/shop',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $block = ContentBlock::query()->where('title', 'Homepage hero')->first();

    expect($block)->not->toBeNull()
        ->and($block->type)->toBe(ContentBlockType::Hero)
        ->and($block->payload)->toBe([
            'image' => null,
            'cta_text' => 'Shop now',
            'cta_url' => 'https://example.com/shop',
        ]);
});

it('creates a featured products content block with ids packed into payload', function (): void {
    $products = Product::factory()->count(2)->create();

    Livewire::actingAs($this->admin)
        ->test(CreateContentBlock::class)
        ->fillForm([
            'type' => ContentBlockType::FeaturedProducts->value,
            'title' => 'Trending products',
            'is_active' => true,
            'sort_order' => 2,
            'featured_product_ids' => $products->pluck('id')->all(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $block = ContentBlock::query()->where('title', 'Trending products')->first();

    expect($block)->not->toBeNull()
        ->and($block->type)->toBe(ContentBlockType::FeaturedProducts)
        ->and($block->payload['ids'])->toEqualCanonicalizing($products->pluck('id')->all());
});
