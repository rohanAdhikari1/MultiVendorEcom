<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class OrderConfirmation extends Component
{
    public Order $order;

    public function mount(string $orderNumber): void
    {
        $order = Order::query()
            ->with(['items.product', 'items.vendor', 'paymentTransactions'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $this->authorize('view', $order);

        $this->order = $order;
    }

    public function render(): View
    {
        return view('livewire.storefront.order-confirmation')
            ->layoutData(['title' => "Order {$this->order->order_number} Confirmed"]);
    }
}
