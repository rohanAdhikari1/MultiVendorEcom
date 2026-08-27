<?php

namespace App\Livewire\Shared;

use App\Models\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CartIndicator extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $this->count = Cart::query()
            ->where('user_id', Auth::id())
            ->with('items')
            ->first()
            ?->items
            ->sum('quantity') ?? 0;
    }

    public function render(): View
    {
        return view('livewire.shared.cart-indicator');
    }
}
