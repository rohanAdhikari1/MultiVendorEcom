<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\RecentlyViewedProducts;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ProductDetail extends Component
{
    /**
     * Related-products grid size, and the minimum same-category count
     * before falling back to "More from this vendor" -- a category with
     * only the product itself (or one sibling) isn't enough to fill a
     * meaningful section.
     */
    private const RELATED_LIMIT = 8;

    private const MIN_SAME_CATEGORY = 2;

    public Product $product;

    public function mount(string $slug): void
    {
        $this->product = Product::query()
            ->with(['vendor', 'category', 'images', 'variants', 'reviews.user'])
            ->where('slug', $slug)
            ->where('status', ProductStatus::Published)
            ->firstOrFail();

        RecentlyViewedProducts::remember($this->product->id);
    }

    public function render(): View
    {
        $visibleReviews = $this->product->reviews->where('is_visible', true);
        $images = $this->product->images->sortByDesc('is_primary')->values();
        $primaryImage = $images->first();

        return view('livewire.storefront.product-detail', [
            'images' => $images,
            'reviews' => $visibleReviews,
            'averageRating' => (float) $visibleReviews->avg('rating'),
            'relatedProducts' => $this->relatedProducts(),
            'recentlyViewedProducts' => RecentlyViewedProducts::forDisplay(excludeProductId: $this->product->id),
        ])->layoutData([
            'title' => $this->product->name,
            'description' => Seo::excerpt($this->product->description),
            'ogImage' => $primaryImage ? Storage::disk('public')->url($primaryImage->path) : null,
            'ogType' => 'product',
        ]);
    }

    /**
     * Same-category products (excluding this one, published only), newest
     * first within an is_featured-first ordering. Falls back to
     * same-vendor products when the category has too few siblings to fill
     * a meaningful section; returns empty when neither yields anything so
     * the view can hide the section entirely rather than show an awkward
     * sparse/empty grid.
     *
     * @return Collection<int, Product>
     */
    private function relatedProducts(): Collection
    {
        $baseQuery = fn (): Builder => Product::query()
            ->with(['vendor', 'primaryImage'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('status', ProductStatus::Published)
            ->where('id', '!=', $this->product->id);

        $sameCategory = collect();

        if ($this->product->category_id !== null) {
            $sameCategory = $baseQuery()
                ->where('category_id', $this->product->category_id)
                ->orderByDesc('is_featured')
                ->orderByDesc('created_at')
                ->limit(self::RELATED_LIMIT)
                ->get();
        }

        if ($sameCategory->count() >= self::MIN_SAME_CATEGORY) {
            return $sameCategory;
        }

        return $baseQuery()
            ->where('vendor_id', $this->product->vendor_id)
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->limit(self::RELATED_LIMIT)
            ->get();
    }
}
