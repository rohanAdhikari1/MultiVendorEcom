<div>
    <x-home-content-blocks />

    @if ($recentlyViewedProducts->isNotEmpty())
        <x-ui.reveal class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <h2 class="text-xl font-bold text-gray-900">Recently Viewed</h2>

            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                @foreach ($recentlyViewedProducts as $viewedProduct)
                    <x-ui.reveal :delay="($loop->index % 8) * 60" wire:key="home-recently-viewed-{{ $viewedProduct->id }}">
                        <x-ui.product-card :product="$viewedProduct" />
                    </x-ui.reveal>
                @endforeach
            </div>
        </x-ui.reveal>
    @endif
</div>
