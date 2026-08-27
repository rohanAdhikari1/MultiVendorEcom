@props(['delay' => 0])

{{--
    Reusable scroll-triggered reveal wrapper: fades and translates its
    content up into place the first time it scrolls into the viewport.
    Pairs `x-intersect` (bundled with Livewire's Alpine build) with the
    `scrollReveal` Alpine.data component registered in resources/js/app.js.

    `prefers-reduced-motion` users start already `revealed`, so this is a
    no-op render for them instead of a fade/translate — see app.js.

    Usage (staggered grid):
        @foreach ($products as $product)
            <x-ui.reveal :delay="($loop->index % 8) * 60" wire:key="product-{{ $product->id }}">
                <x-ui.product-card :product="$product" />
            </x-ui.reveal>
        @endforeach
--}}
<div
    x-data="scrollReveal"
    x-intersect.once="reveal()"
    :class="revealed ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
    style="transition-delay: {{ (int) $delay }}ms"
    {{ $attributes->class(['transition-all duration-500 ease-out']) }}
>
    {{ $slot }}
</div>
