<?php

namespace App\Livewire\Storefront;

use App\Models\Cart as CartModel;
use App\Models\CartItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Cart extends Component
{
    /**
     * Keyed by cart item id, mirrors each row's quantity so the
     * quantity-stepper's Alpine `$wire.set('quantities.{id}', value)`
     * call has somewhere to land — see updated() below for persistence.
     *
     * @var array<int, int>
     */
    public array $quantities = [];

    public function mount(): void
    {
        $this->syncQuantities();
    }

    /**
     * Livewire's generic property-updated hook. quantity-stepper sets
     * nested paths like "quantities.5" via Alpine's $wire.set(), which
     * lands here as $name = "quantities.5" rather than through a
     * dedicated updatedQuantities() method.
     */
    public function updated(string $name, mixed $value): void
    {
        if (! str_starts_with($name, 'quantities.')) {
            return;
        }

        $itemId = (int) substr($name, strlen('quantities.'));

        $this->persistQuantity($itemId, (int) $value);
    }

    public function removeItem(int $itemId): void
    {
        CartItem::query()
            ->whereHas('cart', fn ($query) => $query->where('user_id', Auth::id()))
            ->where('id', $itemId)
            ->delete();

        unset($this->quantities[$itemId]);

        $this->dispatch('cart-updated');
    }

    protected function persistQuantity(int $itemId, int $quantity): void
    {
        $item = CartItem::query()
            ->whereHas('cart', fn ($query) => $query->where('user_id', Auth::id()))
            ->find($itemId);

        if ($item === null) {
            return;
        }

        $quantity = max(1, min(99, $quantity));

        $item->update(['quantity' => $quantity]);

        $this->quantities[$itemId] = $quantity;

        $this->dispatch('cart-updated');
    }

    protected function syncQuantities(): void
    {
        $this->quantities = $this->currentCart()?->items->pluck('quantity', 'id')->all() ?? [];
    }

    protected function currentCart(): ?CartModel
    {
        return CartModel::query()
            ->where('user_id', Auth::id())
            ->with(['items.product.primaryImage', 'items.productVariant', 'items.vendor'])
            ->first();
    }

    public function render(): View
    {
        $cart = $this->currentCart();

        /** @var Collection<int, CartItem> $items */
        $items = $cart->items ?? new Collection;

        return view('livewire.storefront.cart', [
            'itemsByVendor' => $items->groupBy(fn (CartItem $item) => $item->vendor_id),
            'grandTotal' => $items->sum(fn (CartItem $item) => (float) $item->price_snapshot * $item->quantity),
            'isEmpty' => $items->isEmpty(),
        ])->layoutData(['title' => 'Cart']);
    }
}
