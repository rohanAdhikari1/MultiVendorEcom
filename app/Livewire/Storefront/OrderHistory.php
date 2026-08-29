<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class OrderHistory extends Component
{
    use WithPagination;

    public function render(): View
    {
        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->paginate(10);

        return view('livewire.storefront.order-history', ['orders' => $orders])
            ->layoutData(['title' => 'My Orders']);
    }
}
