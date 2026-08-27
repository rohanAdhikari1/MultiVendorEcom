@props(['variant' => null, 'size' => 'md', 'href' => null, 'type' => 'button'])

@php
    use App\Enums\ButtonStyle;
    use App\Services\Theme\ThemeSettingsService;

    // No explicit `variant` was passed: resolve the "primary" look from the
    // admin-configurable `button_style` setting (solid/outline/soft) instead
    // of hardcoding it, so a Filament edit reskins every default button
    // sitewide with zero code changes. An explicit `variant` (e.g.
    // "secondary"/"ghost", or "primary" itself) always wins.
    $resolvedVariant = $variant ?? match (app(ThemeSettingsService::class)->buttonStyle()) {
        ButtonStyle::Outline => 'primary-outline',
        ButtonStyle::Soft => 'primary-soft',
        ButtonStyle::Solid => 'primary',
    };

    // rounded-md / shadow-sm resolve through the `--radius-md` / `--shadow-sm`
    // theme variables (see resources/css/app.css), which ThemeSettingsService
    // overrides at runtime — so these stay plain Tailwind utilities rather
    // than arbitrary values, exactly like the existing bg-primary/text-primary
    // color utilities.
    $variants = [
        'primary' => 'bg-primary text-white hover:opacity-90 shadow-sm',
        'primary-outline' => 'bg-transparent text-primary border-2 border-primary hover:bg-primary/10',
        'primary-soft' => 'bg-primary/10 text-primary hover:bg-primary/20',
        'secondary' => 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 shadow-sm',
        'ghost' => 'bg-transparent text-gray-700 hover:bg-gray-100',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-3 text-base',
    ];

    $classes = trim(
        'inline-flex items-center justify-center gap-2 rounded-md font-semibold transition duration-150 ease-out active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 '
        .($variants[$resolvedVariant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md'])
    );
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
