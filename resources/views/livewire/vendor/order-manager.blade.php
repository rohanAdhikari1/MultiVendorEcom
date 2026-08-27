@php
    use App\Enums\VendorSubOrderStatus;
    use Illuminate\Support\Number;
@endphp

<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Orders</h1>

        <div>
            <label for="statusFilter" class="sr-only">Filter by status</label>
            <select
                id="statusFilter"
                wire:model.live="statusFilter"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            >
                <option value="">All statuses</option>
                @foreach ($statuses as $statusCase)
                    <option value="{{ $statusCase->value }}">{{ $statusCase->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($orders->isEmpty())
        <div class="mt-8">
            <x-ui.empty-state title="No orders yet" message="Orders containing your products will show up here." />
        </div>
    @else
        <div class="mt-6 space-y-6">
            @foreach ($orders as $order)
                <div wire:key="order-{{ $order->id }}" class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 bg-gray-50 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $order->order_number }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $order->items->count() }} of your item(s) &middot; {{ $order->user->name }} &middot; placed {{ $order->placed_at?->format('M j, Y') }}
                            </p>
                        </div>
                    </div>

                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Product</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Qty</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Unit price</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Line total</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                @php
                                    $variant = match ($item->vendor_status) {
                                        VendorSubOrderStatus::Delivered => 'success',
                                        VendorSubOrderStatus::Cancelled => 'danger',
                                        VendorSubOrderStatus::Shipped => 'primary',
                                        default => 'neutral',
                                    };
                                    $nextStatuses = $this->allowedNextStatuses($item->vendor_status);
                                @endphp
                                <tr wire:key="order-item-{{ $item->id }}">
                                    <td class="px-4 py-3 text-gray-900">{{ $item->product_name_snapshot }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $item->quantity }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ Number::currency((float) $item->unit_price_snapshot, 'NPR') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ Number::currency((float) $item->line_total, 'NPR') }}</td>
                                    <td class="px-4 py-3"><x-ui.badge :variant="$variant">{{ $item->vendor_status->label() }}</x-ui.badge></td>
                                    <td class="px-4 py-3">
                                        @if (!empty($nextStatuses))
                                            <select
                                                wire:change="updateStatus({{ $item->id }}, $event.target.value)"
                                                class="rounded-md border border-gray-300 px-2 py-1 text-xs shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                            >
                                                <option value="">Change status&hellip;</option>
                                                @foreach ($nextStatuses as $next)
                                                    <option value="{{ $next->value }}">{{ $next->label() }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-xs text-gray-400">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>
