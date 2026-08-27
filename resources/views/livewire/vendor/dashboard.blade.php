@php
    use App\Enums\VendorSubOrderStatus;
    use Illuminate\Support\Number;
@endphp

<div class="px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Welcome back, {{ $vendorProfile->shop_name }}</h1>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md">
            <p class="text-sm text-gray-500">Total orders</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">
                <x-ui.stat-counter :value="$totalOrders" />
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md">
            <p class="text-sm text-gray-500">Total revenue</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">
                <x-ui.stat-counter :value="$totalRevenue" prefix="NPR " :decimals="2" />
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md">
            <p class="text-sm text-gray-500">Pending fulfillment</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">
                <x-ui.stat-counter :value="$pendingFulfillmentCount" />
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md">
            <p class="text-sm text-gray-500">Published products</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">
                <x-ui.stat-counter :value="$publishedProductCount" />
            </p>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900">Recent orders</h2>

        @if ($recentItems->isEmpty())
            <div class="mt-4">
                <x-ui.empty-state title="No orders yet" message="Orders containing your products will show up here." />
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Order #</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Product</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Qty</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Line total</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($recentItems as $item)
                            @php
                                $variant = match ($item->vendor_status) {
                                    VendorSubOrderStatus::Delivered => 'success',
                                    VendorSubOrderStatus::Cancelled => 'danger',
                                    VendorSubOrderStatus::Shipped => 'primary',
                                    default => 'neutral',
                                };
                            @endphp
                            <tr wire:key="recent-item-{{ $item->id }}">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $item->order->order_number }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $item->product_name_snapshot }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ Number::currency((float) $item->line_total, 'NPR') }}</td>
                                <td class="px-4 py-3"><x-ui.badge :variant="$variant">{{ $item->vendor_status->label() }}</x-ui.badge></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('vendor.orders.index') }}" class="font-medium text-primary hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
