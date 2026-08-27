@props(['block'])

@php
    use Illuminate\Support\Facades\Storage;

    $payload = $block->payload ?? [];
    $imagePath = $payload['image'] ?? null;
    $imageUrl = $imagePath ? Storage::disk('public')->url($imagePath) : null;
    $ctaText = $payload['cta_text'] ?? null;
    $ctaUrl = $payload['cta_url'] ?? '#';
@endphp

<section class="relative overflow-hidden bg-gray-900 transition-all duration-700 ease-out starting:translate-y-4 starting:opacity-0">
    @if ($imageUrl)
        <img
            src="{{ $imageUrl }}"
            alt="{{ $block->title ?? 'Hero banner' }}"
            class="absolute inset-0 h-full w-full object-cover opacity-60"
        >
    @endif

    <div class="relative mx-auto flex max-w-7xl flex-col items-start gap-4 px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
        @if ($block->title)
            <h1 class="max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-5xl">
                {{ $block->title }}
            </h1>
        @endif

        @if ($block->subtitle)
            <p class="max-w-xl text-base text-gray-200 sm:text-lg">
                {{ $block->subtitle }}
            </p>
        @endif

        @if ($ctaText)
            <a
                href="{{ $ctaUrl }}"
                class="mt-2 inline-flex items-center rounded-md bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90"
            >
                {{ $ctaText }}
            </a>
        @endif
    </div>
</section>
