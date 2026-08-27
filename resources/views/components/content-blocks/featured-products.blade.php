@props(['block'])

@php
    use App\Models\Product;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Number;

    $ids = $block->payload['ids'] ?? [];
    $products = empty($ids)
        ? collect()
        : Product::query()->with('primaryImage')->whereIn('id', $ids)->get()->sortBy(fn ($product) => array_search($product->id, $ids))->values();
@endphp

@if ($products->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-10 transition-all duration-700 ease-out starting:translate-y-4 starting:opacity-0 sm:px-6 lg:px-8">
        @if ($block->title)
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ $block->title }}</h2>
        @endif
        @if ($block->subtitle)
            <p class="mt-1 text-sm text-gray-600">{{ $block->subtitle }}</p>
        @endif

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <a href="#" class="group overflow-hidden rounded-lg border border-gray-200 transition-shadow hover:shadow-md">
                    <div class="aspect-square w-full overflow-hidden bg-gray-100">
                        @if ($product->primaryImage?->path)
                            <img
                                src="{{ Storage::disk('public')->url($product->primaryImage->path) }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-cover transition-transform group-hover:scale-105"
                            >
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="truncate text-sm font-medium text-gray-800">{{ $product->name }}</p>
                        <p class="mt-1 text-sm font-semibold text-primary">
                            {{ Number::currency((float) $product->price, 'NPR') }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
