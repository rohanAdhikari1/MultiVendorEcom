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

    public string $search = '';

    public bool $inStockOnly = false;

    public string $minRating = '';

    /**
     * Support `?category={slug}` and `?q={term}` deep-link entry points
     * (e.g. from a category link or the header search bar elsewhere in the
     * app), pre-filtering the catalog on first load.
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

        $searchParam = request()->query('q');

        if ($searchParam !== null) {
            $this->search = (string) $searchParam;
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedInStockOnly(): void
    {
        $this->resetPage();
    }

    public function updatedMinRating(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['categoryId', 'minPrice', 'maxPrice', 'sort', 'search', 'inStockOnly', 'minRating']);
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
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
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

        $searchTerm = trim($this->search);

        $query->when($searchTerm !== '', function (Builder $builder) use ($searchTerm) {
            $builder->where(function (Builder $inner) use ($searchTerm) {
                $inner->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('sku', 'like', "%{$searchTerm}%");
            });
        });

        $query->when($this->inStockOnly, fn (Builder $builder) => $builder->where('stock_quantity', '>', 0));

        // withAvg() above already selects the average rating as
        // reviews_avg_rating for display, but that's a correlated scalar
        // subquery, not a GROUP BY aggregate -- SQLite (unlike MySQL)
        // rejects a HAVING clause referencing it when the outer query has
        // no GROUP BY ("HAVING clause on a non-aggregate query"). A second
        // correlated subquery in WHERE is the portable equivalent: it costs
        // nothing extra round-trip-wise (still one query) and works
        // identically on SQLite and MySQL.
        //
        // The CAST(... AS DECIMAL) around the bound parameter is load-bearing
        // on SQLite: PDO binds parameters with TEXT affinity, and SQLite's
        // type-ordering rules rank TEXT above REAL/INTEGER regardless of
        // value, so `5.0 >= '4'` (an uncast REAL-vs-TEXT comparison) silently
        // evaluates false. Casting the bound value gives it NUMERIC affinity
        // so the comparison is numeric on both SQLite and MySQL.
        $query->when(
            $this->minRating !== '',
            fn (Builder $builder) => $builder->whereRaw(
                '(select avg(r.rating) from reviews r where r.product_id = products.id) >= CAST(? AS DECIMAL(4,2))',
                [(float) $this->minRating],
            ),
        );

        match ($this->sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->orderByDesc('created_at'),
        };

        $activeCategory = $this->categoryId ? Category::query()->find($this->categoryId) : null;

        // Search takes priority over the category filter in the title when
        // both are active -- "Search results for 'x'" reads more naturally
        // than a garbled "Electronics - 'x' - Products" combination, and it
        // matches what the user actually typed to get here.
        $title = match (true) {
            $searchTerm !== '' => "Search results for \"{$searchTerm}\"",
            $activeCategory !== null => "{$activeCategory->name} - Products",
            default => 'Products',
        };

        $description = match (true) {
            $searchTerm !== '' => "Search results for \"{$searchTerm}\" among products from trusted local vendors.",
            $activeCategory !== null => "Browse {$activeCategory->name} products from trusted local vendors.",
            default => 'Browse our full catalog of products from trusted local vendors, with filters for category, price, and more.',
        };

        return view('livewire.storefront.product-catalog', [
            'products' => $query->paginate(12),
            'categories' => $this->activeCategories(),
        ])->layoutData([
            'title' => $title,
            'description' => $description,
        ]);
    }
}
