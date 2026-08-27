@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'bg-gray-100 text-gray-700',
        'success' => 'bg-green-100 text-green-700',
        'warning' => 'bg-amber-100 text-amber-700',
        'danger' => 'bg-red-100 text-red-700',
        'primary' => 'bg-primary/10 text-primary',
    ];

    $classes = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', $classes]) }}>
    {{ $slot }}
</span>
