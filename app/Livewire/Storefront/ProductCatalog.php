<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class ProductCatalog extends Component
{
    use WithPagination;

    public ?int $categoryId = null;

    public ?string $minPrice = null;

    public ?string $maxPrice = null;

    public string $sort = 'newest';

    /**
     * Support a `?category={slug}` deep-link entry point (e.g. from a
     * category link elsewhere in the app), pre-filtering the catalog on
     * first load.
     */
    public function mount(): void
    {
        $categoryParam = request()->query('category');

        if ($categoryParam !== null) {
            $category = is_numeric($categoryParam)
                ? Category::query()->find((int) $categoryParam)
                : Category::query()->where('slug', $categoryParam)->first();

            $this->categoryId = $category?->id;
        }
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['categoryId', 'minPrice', 'maxPrice', 'sort']);
        $this->resetPage();
    }

    /**
     * @return Collection<int, Category>
     */
    protected function activeCategories(): Collection
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function render(): View
    {
        $query = Product::query()
            ->with(['vendor', 'primaryImage'])
            ->where('status', ProductStatus::Published);

        $query->when($this->categoryId, fn (Builder $builder) => $builder->where('category_id', $this->categoryId));

        $query->when(
            $this->minPrice !== null && $this->minPrice !== '',
            fn (Builder $builder) => $builder->where('price', '>=', (float) $this->minPrice),
        );

        $query->when(
            $this->maxPrice !== null && $this->maxPrice !== '',
            fn (Builder $builder) => $builder->where('price', '<=', (float) $this->maxPrice),
        );

        match ($this->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderByDesc('created_at'),
        };

        return view('livewire.storefront.product-catalog', [
            'products' => $query->paginate(12),
            'categories' => $this->activeCategories(),
        ]);
    }
}
