<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ProductDetail extends Component
{
    public Product $product;

    public function mount(string $slug): void
    {
        $this->product = Product::query()
            ->with(['vendor', 'category', 'images', 'variants', 'reviews.user'])
            ->where('slug', $slug)
            ->where('status', ProductStatus::Published)
            ->firstOrFail();
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
        ])->layoutData([
            'title' => $this->product->name,
            'description' => Seo::excerpt($this->product->description),
            'ogImage' => $primaryImage ? Storage::disk('public')->url($primaryImage->path) : null,
            'ogType' => 'product',
        ]);
    }
}
