@props(['product'])

@php
    use Illuminate\Support\Facades\Storage;

    $imagePath = $product->primaryImage?->path;
    // Read the withAvg/withCount aggregates eager-loaded by the caller
    // (ProductCatalog / VendorShopPage) instead of issuing a fresh
    // reviews()->avg()/->count() query per card — the previous version of
    // this component queried live per product, which turned any listing
    // page into an N+1 (2 extra queries per card, scaling with page size).
    $avgRating = (float) ($product->reviews_avg_rating ?? 0);
    $reviewCount = (int) ($product->reviews_count ?? 0);
@endphp

<div {{ $attributes->class(['group overflow-hidden rounded-lg border border-gray-200 bg-white transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-lg']) }}>
    <a href="{{ route('products.show', $product->slug) }}" wire:navigate class="block aspect-square w-full overflow-hidden bg-gray-100">
        @if ($imagePath)
            <img
                src="{{ Storage::disk('public')->url($imagePath) }}"
                alt="{{ $product->name }}"
                class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
            >
        @else
            <div class="flex h-full w-full items-center justify-center text-gray-300">
                <svg viewBox="0 0 24 24" class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 5.25h18M3 5.25a2.25 2.25 0 00-2.25 2.25v9A2.25 2.25 0 003 18.75h18a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0021 5.25M3 5.25h18" />
                </svg>
            </div>
        @endif
    </a>

    <div class="p-3">
        @if ($product->vendor)
            <a href="{{ route('store.show', $product->vendor->shop_slug) }}" wire:navigate class="text-xs text-gray-500 transition-colors hover:text-primary">
                {{ $product->vendor->shop_name }}
            </a>
        @endif

        <a href="{{ route('products.show', $product->slug) }}" wire:navigate class="mt-0.5 block truncate text-sm font-medium text-gray-800 transition-colors hover:text-primary">
            {{ $product->name }}
        </a>

        <div class="mt-1">
            <x-ui.rating-stars :rating="$avgRating" :count="$reviewCount" />
        </div>

        <div class="mt-2">
            <x-ui.price :amount="$product->price" :compare-at="$product->compare_at_price" />
        </div>
    </div>
</div>
