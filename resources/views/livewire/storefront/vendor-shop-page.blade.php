@php
    use Illuminate\Support\Facades\Storage;

    $bannerUrl = $vendor->banner_path ? Storage::disk('public')->url($vendor->banner_path) : null;
    $logoUrl = $vendor->logo_path ? Storage::disk('public')->url($vendor->logo_path) : null;
@endphp

<div>
    <div class="h-48 w-full overflow-hidden bg-gray-100 sm:h-64">
        @if ($bannerUrl)
            <img src="{{ $bannerUrl }}" alt="{{ $vendor->shop_name }} banner" class="h-full w-full object-cover">
        @else
            <div class="h-full w-full bg-linear-to-r from-primary/10 to-secondary/10"></div>
        @endif
    </div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="-mt-10 flex flex-col items-start gap-4 sm:flex-row sm:items-end">
            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full border-4 border-white bg-gray-100 shadow-sm">
                @if ($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $vendor->shop_name }} logo" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-lg font-semibold text-gray-400">
                        {{ substr($vendor->shop_name, 0, 1) }}
                    </div>
                @endif
            </div>

            <div class="pb-1">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $vendor->shop_name }}</h1>
                @if ($vendor->description)
                    <p class="mt-1 max-w-2xl text-sm text-gray-600">{{ $vendor->description }}</p>
                @endif
            </div>
        </div>

        <div class="mt-8 border-t border-gray-200 pt-8">
            <h2 class="text-lg font-semibold text-gray-900">Products</h2>

            @if ($products->isEmpty())
                <div class="mt-6">
                    <x-ui.empty-state
                        title="No products yet"
                        message="This vendor hasn't listed any products yet."
                    />
                </div>
            @else
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        <x-ui.reveal :delay="($loop->index % 8) * 60" wire:key="product-{{ $product->id }}">
                            <x-ui.product-card :product="$product" />
                        </x-ui.reveal>
                    @endforeach
                </div>

                <div class="mt-8 pb-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
