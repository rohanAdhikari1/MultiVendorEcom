@props(['block'])

@php
    use Illuminate\Support\Facades\Storage;

    $items = $block->payload['items'] ?? [];
@endphp

@if (! empty($items))
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($block->title)
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ $block->title }}</h2>
        @endif
        @if ($block->subtitle)
            <p class="mt-1 text-sm text-gray-600">{{ $block->subtitle }}</p>
        @endif

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                <a href="{{ $item['link_url'] ?? '#' }}" class="group overflow-hidden rounded-lg border border-gray-200">
                    @if (! empty($item['image']))
                        <div class="aspect-video w-full overflow-hidden bg-gray-100">
                            <img
                                src="{{ Storage::disk('public')->url($item['image']) }}"
                                alt="{{ $item['title'] ?? '' }}"
                                class="h-full w-full object-cover transition-transform group-hover:scale-105"
                            >
                        </div>
                    @endif
                    @if (! empty($item['title']))
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-800">{{ $item['title'] }}</p>
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif
