@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <nav class="text-sm text-gray-500">
        <a href="{{ route('products.index') }}" class="hover:text-primary">All Products</a>
        @if ($product->category)
            <span class="mx-1">/</span>
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="hover:text-primary">
                {{ $product->category->name }}
            </a>
        @endif
    </nav>

    <div class="mt-4 grid grid-cols-1 gap-10 lg:grid-cols-2">
        <div x-data="{ active: {{ optional($images->first())->path ? "'".Storage::disk('public')->url($images->first()->path)."'" : 'null' }} }">
            <div class="aspect-square w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                <template x-if="active">
                    <img :src="active" alt="{{ $product->name }}" class="h-full w-full object-cover">
                </template>
                <template x-if="!active">
                    <div class="flex h-full w-full items-center justify-center text-gray-300">
                        <svg viewBox="0 0 24 24" class="h-16 w-16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 5.25h18M3 5.25a2.25 2.25 0 00-2.25 2.25v9A2.25 2.25 0 003 18.75h18a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0021 5.25M3 5.25h18" />
                        </svg>
                    </div>
                </template>
            </div>

            @if ($images->count() > 1)
                <div class="mt-3 grid grid-cols-5 gap-2">
                    @foreach ($images as $image)
                        <button
                            type="button"
                            @click="active = '{{ Storage::disk('public')->url($image->path) }}'"
                            class="aspect-square overflow-hidden rounded-md border border-gray-200 hover:border-primary"
                        >
                            <img src="{{ Storage::disk('public')->url($image->path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            @if ($product->vendor)
                <a href="{{ route('store.show', $product->vendor->shop_slug) }}" class="text-sm text-gray-500 hover:text-primary">
                    {{ $product->vendor->shop_name }}
                </a>
            @endif

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-gray-900">{{ $product->name }}</h1>

            <div class="mt-2 flex items-center gap-2">
                <x-ui.rating-stars :rating="$averageRating" :count="$reviews->count()" />
                @if ($product->stock_quantity > 0)
                    <x-ui.badge variant="success">In Stock</x-ui.badge>
                @else
                    <x-ui.badge variant="danger">Out of Stock</x-ui.badge>
                @endif
            </div>

            <div class="mt-4">
                <x-ui.price :amount="$product->price" :compare-at="$product->compare_at_price" class="text-2xl" />
            </div>

            @if ($product->description)
                <p class="mt-6 whitespace-pre-line text-sm leading-relaxed text-gray-600">{{ $product->description }}</p>
            @endif

            @if ($product->variants->isNotEmpty())
                <div class="mt-6">
                    <label for="variant" class="block text-sm font-medium text-gray-700">Choose an option</label>
                    <select id="variant" class="mt-1 block w-full max-w-xs rounded-md border border-gray-300 py-2 pl-3 pr-8 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        @foreach ($product->variants as $variant)
                            <option value="{{ $variant->id }}">
                                {{ collect($variant->attributes ?? [])->map(fn ($value, $key) => "{$key}: {$value}")->implode(', ') ?: $variant->sku }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="mt-8">
                <livewire:shared.add-to-cart-button :product="$product" />
            </div>
        </div>
    </div>

    <div class="mt-16 border-t border-gray-200 pt-10">
        <h2 class="text-xl font-bold text-gray-900">Reviews</h2>

        @if ($reviews->isEmpty())
            <p class="mt-4 text-sm text-gray-500">No reviews yet for this product.</p>
        @else
            <div class="mt-6 space-y-6">
                @foreach ($reviews as $review)
                    <div class="border-b border-gray-100 pb-6 last:border-b-0">
                        <div class="flex items-center gap-2">
                            <x-ui.rating-stars :rating="$review->rating" />
                            <span class="text-sm font-medium text-gray-900">{{ $review->user->name }}</span>
                        </div>
                        @if ($review->body)
                            <p class="mt-2 text-sm text-gray-600">{{ $review->body }}</p>
                        @endif

                        @if ($review->vendor_reply)
                            <div class="mt-3 ml-4 rounded-md border-l-2 border-primary/30 bg-gray-50 px-4 py-3">
                                <p class="text-xs font-semibold text-gray-700">
                                    Reply from {{ $product->vendor?->shop_name }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600">{{ $review->vendor_reply }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ $review->vendor_replied_at?->format('M j, Y') }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($relatedProducts->isNotEmpty())
        <x-ui.reveal class="mt-16 border-t border-gray-200 pt-10">
            <h2 class="text-xl font-bold text-gray-900">You might also like</h2>

            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                @foreach ($relatedProducts as $relatedProduct)
                    <x-ui.reveal :delay="($loop->index % 8) * 60" wire:key="related-{{ $relatedProduct->id }}">
                        <x-ui.product-card :product="$relatedProduct" />
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.reveal>
    @endif

    @if ($recentlyViewedProducts->isNotEmpty())
        <x-ui.reveal class="mt-16 border-t border-gray-200 pt-10">
            <h2 class="text-xl font-bold text-gray-900">Recently Viewed</h2>

            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                @foreach ($recentlyViewedProducts as $viewedProduct)
                    <x-ui.reveal :delay="($loop->index % 8) * 60" wire:key="recently-viewed-{{ $viewedProduct->id }}">
                        <x-ui.product-card :product="$viewedProduct" />
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.reveal>
    @endif
</div>
