<?php

namespace App\Livewire\Storefront;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class Wishlist extends Component
{
    use WithPagination;

    /**
     * Removing an item from this page happens via the child
     * <livewire:shared.wishlist-button> toggling itself off; without this
     * listener the parent grid wouldn't know to re-render and drop the
     * now-unwishlisted product from the list.
     */
    #[On('wishlist-updated')]
    public function refresh(): void {}

    public function render(): View
    {
        // Query from Product (joined to wishlist_items rather than eager
        // loading through WishlistItem) so this mirrors the exact same
        // with/withAvg/withCount shape ProductCatalog/VendorShopPage use for
        // <x-ui.product-card> -- avoiding an N+1 across the grid -- while
        // still ordering by "most recently wishlisted" via a plain portable
        // JOIN + ORDER BY (works on SQLite, this app's primary connection,
        // unlike MySQL-only tricks such as FIELD()).
        $products = Product::query()
            ->select('products.*')
            ->join('wishlist_items', 'wishlist_items.product_id', '=', 'products.id')
            ->where('wishlist_items.user_id', Auth::id())
            ->with(['vendor', 'category', 'primaryImage'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('wishlist_items.created_at')
            ->paginate(12);

        return view('livewire.storefront.wishlist', [
            'products' => $products,
        ])->layoutData(['title' => 'Wishlist']);
    }
}
