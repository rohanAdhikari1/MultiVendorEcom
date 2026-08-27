@php
    use App\Services\Theme\ThemeSettingsService;

    $themeService = app(ThemeSettingsService::class);
    $settings = $themeService->settings();
    $vendorProfile = auth()->user()?->vendorProfile;

    $navItems = [
        ['route' => 'vendor.dashboard', 'label' => 'Dashboard'],
        ['route' => 'vendor.products.index', 'label' => 'Products'],
        ['route' => 'vendor.orders.index', 'label' => 'Orders'],
    ];
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
        <div class="mx-auto flex max-w-7xl">
            <aside class="hidden w-56 shrink-0 border-r border-gray-200 bg-white lg:flex lg:min-h-screen lg:flex-col">
                <div class="border-b border-gray-100 px-5 py-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Vendor Dashboard</p>
                    <p class="mt-1 truncate text-sm font-semibold text-gray-900">{{ $vendorProfile?->shop_name ?? $settings->site_name }}</p>
                </div>

                <nav class="flex-1 space-y-1 px-3 py-4">
                    @foreach ($navItems as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'block rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                'bg-primary/10 text-primary' => request()->routeIs($item['route'].'*'),
                                'text-gray-700 hover:bg-gray-100 hover:text-gray-900' => ! request()->routeIs($item['route'].'*'),
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="space-y-1 border-t border-gray-100 px-3 py-4">
                    <a href="{{ url('/') }}" class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900">
                        &larr; Back to storefront
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900">
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <div class="min-h-screen flex-1">
                <header class="border-b border-gray-200 bg-white lg:hidden">
                    <div class="flex h-14 items-center justify-between gap-4 px-4">
                        <span class="truncate text-sm font-semibold text-primary">{{ $vendorProfile?->shop_name ?? 'Vendor Dashboard' }}</span>
                        <nav class="flex items-center gap-3 text-sm">
                            @foreach ($navItems as $item)
                                <a href="{{ route($item['route']) }}" class="{{ request()->routeIs($item['route'].'*') ? 'font-semibold text-primary' : 'text-gray-600' }}">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                            <a href="{{ url('/') }}" class="text-gray-600">Storefront</a>
                        </nav>
                    </div>
                </header>

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
