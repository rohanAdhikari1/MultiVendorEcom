@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;
@endphp

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">My Orders</h1>

    @if ($orders->isEmpty())
        <div class="mt-8">
            <x-ui.empty-state title="No orders yet" message="Once you place an order, it will show up here.">
                <x-ui.button :href="route('products.index')">Start Shopping</x-ui.button>
            </x-ui.empty-state>
        </div>
    @else
        <div class="mt-8 overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Order #</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Items</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Total</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Payment</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($orders as $order)
                        @php
                            $statusVariant = match ($order->status) {
                                OrderStatus::Delivered => 'success',
                                OrderStatus::Cancelled => 'danger',
                                OrderStatus::Shipped, OrderStatus::PartiallyShipped => 'primary',
                                default => 'neutral',
                            };

                            $paymentVariant = match ($order->payment_status) {
                                PaymentStatus::Paid => 'success',
                                PaymentStatus::Failed => 'danger',
                                PaymentStatus::Refunded => 'warning',
                                default => 'neutral',
                            };
                        @endphp
                        <tr wire:key="order-{{ $order->id }}">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $order->order_number }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->placed_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->items_count }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ Illuminate\Support\Number::currency((float) $order->grand_total, 'NPR') }}</td>
                            <td class="px-4 py-3"><x-ui.badge :variant="$paymentVariant">{{ $order->payment_status->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3"><x-ui.badge :variant="$statusVariant">{{ $order->status->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('orders.confirmation', $order->order_number) }}" class="font-medium text-primary hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>
