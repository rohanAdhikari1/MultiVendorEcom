@props(['rating' => 0, 'count' => null])

@php
    $rating = max(0, min(5, (float) $rating));
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
    $empty = 5 - $full - ($half ? 1 : 0);
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1']) }} aria-label="{{ number_format($rating, 1) }} out of 5 stars">
    @for ($i = 0; $i < $full; $i++)
        <svg viewBox="0 0 20 20" class="h-4 w-4 fill-accent" aria-hidden="true">
            <path d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.9l-5.2 2.62.99-5.8-4.21-4.1 5.82-.85z" />
        </svg>
    @endfor

    @if ($half)
        <svg viewBox="0 0 20 20" class="h-4 w-4" aria-hidden="true">
            <defs>
                <linearGradient id="half-star-{{ $attributes->get('id', spl_object_id($attributes)) }}">
                    <stop offset="50%" stop-color="currentColor" class="text-accent" />
                    <stop offset="50%" stop-color="transparent" stop-opacity="1" />
                </linearGradient>
            </defs>
            <path
                d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.9l-5.2 2.62.99-5.8-4.21-4.1 5.82-.85z"
                fill="url(#half-star-{{ $attributes->get('id', spl_object_id($attributes)) }})"
                stroke="currentColor"
                class="text-accent"
                stroke-width="0.5"
            />
        </svg>
    @endif

    @for ($i = 0; $i < $empty; $i++)
        <svg viewBox="0 0 20 20" class="h-4 w-4 fill-none stroke-gray-300" aria-hidden="true">
            <path
                d="M10 1.5l2.6 5.27 5.82.85-4.21 4.1.99 5.8L10 14.9l-5.2 2.62.99-5.8-4.21-4.1 5.82-.85z"
                stroke-width="1"
            />
        </svg>
    @endfor

    @if (! is_null($count))
        <span class="ml-1 text-xs text-gray-500">({{ $count }})</span>
    @endif
</span>
