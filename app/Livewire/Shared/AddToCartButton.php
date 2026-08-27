<?php

namespace App\Livewire\Shared;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AddToCartButton extends Component
{
    public Product $product;

    public ?int $productVariantId = null;

    public int $quantity = 1;

    public bool $added = false;

    public function add(): void
    {
        if (! Auth::check()) {
            return;
        }

        $variant = $this->productVariantId
            ? ProductVariant::query()->where('product_id', $this->product->id)->find($this->productVariantId)
            : null;

        $quantity = max(1, $this->quantity);

        $cart = Cart::query()->firstOrCreate(['user_id' => Auth::id()]);

        $price = $this->product->price;

        if ($variant !== null && $variant->price_override !== null) {
            $price = $variant->price_override;
        }

        $item = CartItem::query()->firstOrNew([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $variant?->id,
        ]);

        $item->vendor_id = $this->product->vendor_id;
        $item->quantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $item->price_snapshot = $price;
        $item->save();

        $this->added = true;

        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('livewire.shared.add-to-cart-button');
    }
}
