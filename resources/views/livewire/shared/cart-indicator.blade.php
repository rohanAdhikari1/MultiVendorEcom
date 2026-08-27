@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Number;
@endphp

{{--
    Cart icon + slide-in mini-cart drawer. The drawer only opens on an
    explicit click (not hover) — hover-to-open panels are easy to trigger
    by accident while scrolling past the header and are awkward on touch,
    so a click is the more predictable, more accessible affordance here.

    Adding an item does NOT auto-open the drawer: the existing toast
    notification ("Added to cart.") already confirms the action without
    interrupting the page, and popping open a full drawer on every add
    felt more intrusive than delightful for a marketplace where people
    often add several items in a row. The badge count still updates live
    via the cart-updated listener either way.
--}}
<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="relative">
    <button
        type="button"
        id="cart-indicator-trigger"
        x-on:click="open = true"
        class="relative inline-flex items-center rounded-md p-2 text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900"
        aria-label="Cart items: {{ $count }}"
        aria-haspopup="dialog"
        :aria-expanded="open.toString()"
        aria-controls="mini-cart-drawer"
    >
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.87-4.804 2.182-7.43a.75.75 0 00-.749-.833H5.106M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
        </svg>

        @if ($count > 0)
            <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-white">
                {{ $count }}
            </span>
        @endif
    </button>

    {{-- Backdrop --}}
    <div
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="open = false"
        class="fixed inset-0 z-40 bg-gray-900/40"
        aria-hidden="true"
    ></div>

    {{-- Slide-in drawer --}}
    <div
        id="mini-cart-drawer"
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-sm flex-col bg-white shadow-xl"
        role="dialog"
        aria-modal="true"
        aria-label="Cart preview"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-4">
            <h2 class="text-base font-semibold text-gray-900">Your Cart</h2>
            <button
                type="button"
                id="mini-cart-close"
                x-on:click="open = false"
                class="rounded-md p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                aria-label="Close cart preview"
            >
                &times;
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4">
            @if ($previewItems->isEmpty())
                <x-ui.empty-state title="Your cart is empty" message="Add something you like to see it here." />
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($previewItems as $item)
                        <li wire:key="mini-cart-item-{{ $item->id }}" class="flex items-center gap-3 py-3">
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md bg-gray-100">
                                @if ($item->product?->primaryImage)
                                    <img
                                        src="{{ Storage::disk('public')->url($item->product->primaryImage->path) }}"
                                        alt="{{ $item->product->name }}"
                                        class="h-full w-full object-cover"
                                    >
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-800">{{ $item->product?->name }}</p>
                                <p class="text-xs text-gray-500">Qty {{ $item->quantity }}</p>
                            </div>

                            <p class="shrink-0 text-sm font-semibold text-gray-900">
                                {{ Number::currency((float) $item->price_snapshot * $item->quantity, 'NPR') }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($previewItems->isNotEmpty())
            <div class="border-t border-gray-200 px-4 py-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-semibold text-gray-900">{{ Number::currency((float) $subtotal, 'NPR') }}</span>
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    <x-ui.button href="{{ route('cart') }}" wire:navigate class="w-full justify-center">
                        View Cart
                    </x-ui.button>
                    <x-ui.button href="{{ route('checkout') }}" wire:navigate variant="secondary" class="w-full justify-center">
                        Checkout
                    </x-ui.button>
                </div>
            </div>
        @endif
    </div>
</div>
