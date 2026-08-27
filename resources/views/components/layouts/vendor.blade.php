{{--
    Minimal placeholder shell for the vendor area. A later phase owns the
    real vendor dashboard and will flesh this out further; this only wires
    up the theme <head> mechanism shared with the storefront layout.
--}}
@php
    use App\Services\Theme\ThemeSettingsService;

    $themeService = app(ThemeSettingsService::class);
    $settings = $themeService->settings();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ isset($title) ? $title.' - Vendor Dashboard' : 'Vendor Dashboard' }} - {{ $settings->site_name }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">

        <style>{!! $themeService->cssVariables() !!}</style>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex h-14 max-w-7xl items-center px-4 sm:px-6 lg:px-8">
                <span class="text-sm font-semibold text-primary">Vendor Dashboard</span>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>
    </body>
</html>
