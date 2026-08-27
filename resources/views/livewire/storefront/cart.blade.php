@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Your Cart</h1>

    @if ($isEmpty)
        <div class="mt-8">
            <x-ui.empty-state title="Your cart is empty" message="Browse the catalog and add something you like.">
                <x-ui.button :href="route('products.index')">Continue Shopping</x-ui.button>
            </x-ui.empty-state>
        </div>
    @else
        <div class="mt-8 space-y-8">
            @foreach ($itemsByVendor as $vendorId => $vendorItems)
                @php $vendor = $vendorItems->first()->vendor; @endphp

                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <a href="{{ route('store.show', $vendor->shop_slug) }}" class="text-sm font-semibold text-gray-800 hover:text-primary">
                            {{ $vendor->shop_name }}
                        </a>
                    </div>

                    <ul class="divide-y divide-gray-100">
                        @foreach ($vendorItems as $item)
                            <li wire:key="cart-item-{{ $item->id }}" class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center">
                                <a href="{{ route('products.show', $item->product->slug) }}" class="h-20 w-20 shrink-0 overflow-hidden rounded-md bg-gray-100">
                                    @if ($item->product->primaryImage)
                                        <img
                                            src="{{ Storage::disk('public')->url($item->product->primaryImage->path) }}"
                                            alt="{{ $item->product->name }}"
                                            class="h-full w-full object-cover"
                                        >
                                    @endif
                                </a>

                                <div class="flex-1">
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="font-medium text-gray-900 hover:text-primary">
                                        {{ $item->product->name }}
                                    </a>

                                    @if ($item->productVariant)
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ collect($item->productVariant->attributes ?? [])->map(fn ($value, $key) => "{$key}: {$value}")->implode(', ') }}
                                        </p>
                                    @endif

                                    <div class="mt-1">
                                        <x-ui.price :amount="$item->price_snapshot" />
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-ui.quantity-stepper
                                        :name="'quantity-'.$item->id"
                                        :model="'quantities.'.$item->id"
                                        :value="$quantities[$item->id] ?? $item->quantity"
                                    />

                                    <p class="w-24 text-right text-sm font-semibold text-gray-900">
                                        {{ Illuminate\Support\Number::currency((float) $item->price_snapshot * ($quantities[$item->id] ?? $item->quantity), 'NPR') }}
                                    </p>

                                    <button
                                        type="button"
                                        wire:click="removeItem({{ $item->id }})"
                                        wire:confirm="Remove this item from your cart?"
                                        class="text-sm text-gray-400 transition-colors hover:text-red-600"
                                        aria-label="Remove item"
                                    >
                                        &times;
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-4 py-2 text-sm">
                        <span class="text-gray-500">Vendor subtotal:</span>
                        <span class="font-semibold text-gray-900">
                            {{ Illuminate\Support\Number::currency((float) $vendorItems->sum(fn ($item) => (float) $item->price_snapshot * ($quantities[$item->id] ?? $item->quantity)), 'NPR') }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex flex-col items-end gap-2 border-t border-gray-200 pt-6">
            <div class="flex items-baseline gap-3 text-lg">
                <span class="text-gray-600">Cart Total:</span>
                <span class="font-bold text-gray-900">{{ Illuminate\Support\Number::currency((float) $grandTotal, 'NPR') }}</span>
            </div>
            <p class="text-xs text-gray-400">Shipping and final total are calculated at checkout.</p>

            <x-ui.button :href="route('checkout')" size="lg" class="mt-2">
                Proceed to Checkout
            </x-ui.button>
        </div>
    @endif
</div>
