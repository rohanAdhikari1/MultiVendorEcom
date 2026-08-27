<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-6 lg:flex-row">
        <aside class="w-full shrink-0 lg:w-64">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Filters</h2>
                    <button type="button" wire:click="resetFilters" class="text-xs font-medium text-primary hover:opacity-90">
                        Reset
                    </button>
                </div>

                <div class="mt-4">
                    <label for="category" class="block text-xs font-medium text-gray-700">Category</label>
                    <select
                        id="category"
                        wire:model.live="categoryId"
                        class="mt-1 block w-full rounded-md border border-gray-300 py-2 pl-3 pr-8 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <span class="block text-xs font-medium text-gray-700">Price range (NPR)</span>
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model.live.debounce.400ms="minPrice"
                            placeholder="Min"
                            class="block w-full rounded-md border border-gray-300 px-2 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                        <span class="text-gray-400">&ndash;</span>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model.live.debounce.400ms="maxPrice"
                            placeholder="Max"
                            class="block w-full rounded-md border border-gray-300 px-2 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                    </div>
                </div>

                <div class="mt-4">
                    <label for="sort" class="block text-xs font-medium text-gray-700">Sort by</label>
                    <select
                        id="sort"
                        wire:model.live="sort"
                        class="mt-1 block w-full rounded-md border border-gray-300 py-2 pl-3 pr-8 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="newest">Newest</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>
            </div>
        </aside>

        <div class="flex-1">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">All Products</h1>

            @if ($products->isEmpty())
                <div class="mt-6">
                    <x-ui.empty-state
                        title="No products found"
                        message="Try adjusting your filters or check back later."
                    />
                </div>
            @else
                @php
                    $catalogLoadingTarget = 'categoryId,minPrice,maxPrice,sort,gotoPage,nextPage,previousPage';
                @endphp

                {{-- Skeleton placeholders while a filter/sort/page change is in flight. --}}
                <div wire:loading wire:target="{{ $catalogLoadingTarget }}" class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @for ($i = 0; $i < 8; $i++)
                        <div class="overflow-hidden rounded-lg border border-gray-200 p-3">
                            <x-ui.skeleton class="aspect-square w-full" />
                            <x-ui.skeleton class="mt-3 h-4 w-3/4" />
                            <x-ui.skeleton class="mt-2 h-4 w-1/2" />
                        </div>
                    @endfor
                </div>

                <div wire:loading.remove wire:target="{{ $catalogLoadingTarget }}" class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        <x-ui.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                    @endforeach
                </div>

                <div wire:loading.remove wire:target="{{ $catalogLoadingTarget }}" class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
