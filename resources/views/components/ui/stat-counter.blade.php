@props(['value', 'prefix' => '', 'suffix' => '', 'decimals' => 0, 'duration' => 900])

@php
    $numericValue = (float) $value;
    $formatted = number_format($numericValue, (int) $decimals);
@endphp

{{--
    Reusable animated stat tile number: counts up from 0 to `value` on
    page load using an ease-out curve (see the `statCounter` Alpine.data
    component in resources/js/app.js). `prefers-reduced-motion` users see
    the final value immediately, no animation.

    The server-rendered `$formatted` value is the no-JS fallback, and
    `data-target-value` (the raw numeric value) is what Pest markup tests
    assert against.

    Usage:
        <x-ui.stat-counter :value="$totalOrders" />
        <x-ui.stat-counter :value="$totalRevenue" prefix="Rs. " :decimals="2" />
--}}
<span
    x-data="statCounter({ value: {{ $numericValue }}, decimals: {{ (int) $decimals }}, duration: {{ (int) $duration }} })"
    x-init="start()"
    data-stat-counter
    data-target-value="{{ $numericValue }}"
    {{ $attributes }}
>{{ $prefix }}<span x-text="display">{{ $formatted }}</span>{{ $suffix }}</span>
