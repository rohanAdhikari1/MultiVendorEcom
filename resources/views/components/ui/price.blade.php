@props(['amount', 'compareAt' => null])

@php
    use Illuminate\Support\Number;

    $hasDiscount = $compareAt !== null && (float) $compareAt > (float) $amount;
@endphp

<span {{ $attributes->class(['inline-flex items-baseline gap-2']) }}>
    <span class="font-semibold text-primary">{{ Number::currency((float) $amount, 'NPR') }}</span>
    @if ($hasDiscount)
        <span class="text-sm text-gray-400 line-through">{{ Number::currency((float) $compareAt, 'NPR') }}</span>
    @endif
</span>
