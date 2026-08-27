@php
    use App\Enums\OrderStatus;
    use App\Enums\PaymentStatus;

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

<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="rounded-lg border border-gray-200 p-6 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
            <svg viewBox="0 0 24 24" class="h-7 w-7 text-green-600" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>

        <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900">Thank you for your order!</h1>
        <p class="mt-1 text-sm text-gray-600">Order <span class="font-semibold text-gray-900">#{{ $order->order_number }}</span> has been placed.</p>

        <div class="mt-4 flex items-center justify-center gap-2">
            <x-ui.badge :variant="$statusVariant">{{ $order->status->label() }}</x-ui.badge>
            <x-ui.badge :variant="$paymentVariant">{{ $order->payment_status->label() }}</x-ui.badge>
        </div>
    </div>

    <div class="mt-8 rounded-lg border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900">Order Details</h2>

        <ul class="mt-4 divide-y divide-gray-100">
            @foreach ($order->items as $item)
                <li class="flex items-center justify-between py-3 text-sm">
                    <div>
                        <p class="font-medium text-gray-900">{{ $item->product_name_snapshot }}</p>
                        <p class="text-gray-500">{{ $item->vendor->shop_name }} &middot; Qty {{ $item->quantity }}</p>
                    </div>
                    <span class="font-medium text-gray-900">{{ Illuminate\Support\Number::currency((float) $item->line_total, 'NPR') }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-600">Subtotal</dt>
                <dd class="text-gray-900">{{ Illuminate\Support\Number::currency((float) $order->subtotal, 'NPR') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-600">Shipping</dt>
                <dd class="text-gray-900">{{ Illuminate\Support\Number::currency((float) $order->shipping_total, 'NPR') }}</dd>
            </div>
            <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-semibold">
                <dt class="text-gray-900">Total</dt>
                <dd class="text-gray-900">{{ Illuminate\Support\Number::currency((float) $order->grand_total, 'NPR') }}</dd>
            </div>
        </dl>
    </div>

    <div class="mt-8 rounded-lg border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900">Shipping To</h2>
        <p class="mt-2 text-sm text-gray-700">{{ $order->shipping_recipient_name }} &middot; {{ $order->shipping_phone }}</p>
        <p class="text-sm text-gray-600">
            {{ collect([$order->shipping_street_address, $order->shipping_municipality, 'Ward '.$order->shipping_ward_no, $order->shipping_district, $order->shipping_province])->filter()->implode(', ') }}
        </p>
    </div>

    <div class="mt-8 flex justify-center gap-3">
        <x-ui.button :href="route('orders.index')" variant="secondary">View All Orders</x-ui.button>
        <x-ui.button :href="route('products.index')">Continue Shopping</x-ui.button>
    </div>
</div>
