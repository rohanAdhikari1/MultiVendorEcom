<?php

namespace App\Livewire\Shared;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CartIndicator extends Component
{
    public int $count = 0;

    /**
     * Per-request cache for the mini-cart drawer's item list, so
     * refreshCount() (which needs the same rows to sum quantities) and
     * render() (which needs them for the drawer) don't each issue their
     * own query. Not a public property: it's request-local scratch data,
     * not part of the component's synced Livewire state.
     *
     * @var Collection<int, CartItem>|null
     */
    protected ?Collection $cachedItems = null;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $this->count = $this->items()->sum('quantity');
    }

    /**
     * @return Collection<int, CartItem>
     */
    protected function items(): Collection
    {
        if ($this->cachedItems === null) {
            $cart = $this->currentCart();

            $this->cachedItems = $cart !== null ? $cart->items : new Collection;
        }

        return $this->cachedItems;
    }

    protected function currentCart(): ?Cart
    {
        return Cart::query()
            ->where('user_id', Auth::id())
            ->with(['items.product.primaryImage', 'items.vendor'])
            ->first();
    }

    public function render(): View
    {
        $items = $this->items();

        return view('livewire.shared.cart-indicator', [
            'previewItems' => $items,
            'subtotal' => $items->sum(fn (CartItem $item) => (float) $item->price_snapshot * $item->quantity),
        ]);
    }
}
