@props(['block'])

@php
    use App\Models\Category;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $ids = $block->payload['ids'] ?? [];
    $categories = empty($ids)
        ? collect()
        : Category::query()->whereIn('id', $ids)->get()->sortBy(fn ($category) => array_search($category->id, $ids))->values();
@endphp

@if ($categories->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($block->title)
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ $block->title }}</h2>
        @endif
        @if ($block->subtitle)
            <p class="mt-1 text-sm text-gray-600">{{ $block->subtitle }}</p>
        @endif

        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($categories as $category)
                <a href="#" class="group flex flex-col items-center gap-3 rounded-lg border border-gray-200 p-4 text-center transition-colors hover:border-primary">
                    <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-full bg-gray-100">
                        @if ($category->image_path)
                            <img
                                src="{{ Storage::disk('public')->url($category->image_path) }}"
                                alt="{{ $category->name }}"
                                class="h-full w-full object-cover"
                            >
                        @else
                            <span class="text-lg font-semibold text-secondary">{{ Str::substr($category->name, 0, 1) }}</span>
                        @endif
                    </div>
                    <span class="text-sm font-medium text-gray-800 group-hover:text-primary">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
