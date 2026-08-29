<?php

namespace App\Livewire\Shared;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class WishlistButton extends Component
{
    public Product $product;

    public bool $isWishlisted = false;

    public function mount(): void
    {
        if (! Auth::check()) {
            return;
        }

        $this->isWishlisted = WishlistItem::query()
            ->where('user_id', Auth::id())
            ->where('product_id', $this->product->id)
            ->exists();
    }

    public function toggle(): void
    {
        if (! Auth::check()) {
            return;
        }

        $item = WishlistItem::query()
            ->where('user_id', Auth::id())
            ->where('product_id', $this->product->id)
            ->first();

        if ($item !== null) {
            $item->delete();
            $this->isWishlisted = false;
            $message = 'Removed from wishlist.';
        } else {
            WishlistItem::query()->create([
                'user_id' => Auth::id(),
                'product_id' => $this->product->id,
            ]);
            $this->isWishlisted = true;
            $message = 'Added to wishlist.';
        }

        $this->dispatch('wishlist-updated');
        $this->dispatch('notify', message: $message, type: 'success');
    }

    public function render(): View
    {
        return view('livewire.shared.wishlist-button');
    }
}
