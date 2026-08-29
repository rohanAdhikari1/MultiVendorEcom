<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">My Wishlist</h1>

    @if ($products->isEmpty())
        <div class="mt-6">
            <x-ui.empty-state
                title="Your wishlist is empty"
                message="Save products you love by tapping the heart icon on any product."
            />
        </div>
    @else
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <x-ui.reveal :delay="($loop->index % 8) * 60" wire:key="wishlist-product-{{ $product->id }}">
                    <x-ui.product-card :product="$product" />
                </x-ui.reveal>
            @endforeach
        </div>

        <div class="mt-8 pb-8">
            {{ $products->links() }}
        </div>
    @endif
</div>
