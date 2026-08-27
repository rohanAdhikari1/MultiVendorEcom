@props(['block'])

@php
    use Illuminate\Support\Facades\Storage;

    $payload = $block->payload ?? [];
    $imagePath = $payload['image'] ?? null;
    $imageUrl = $imagePath ? Storage::disk('public')->url($imagePath) : null;
    $linkUrl = $payload['link_url'] ?? '#';
@endphp

@if ($imageUrl)
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ $linkUrl }}" class="block overflow-hidden rounded-lg">
            <img
                src="{{ $imageUrl }}"
                alt="{{ $block->title ?? 'Promotional banner' }}"
                class="h-auto w-full object-cover transition-transform duration-300 hover:scale-[1.01]"
            >
        </a>
    </section>
@endif
