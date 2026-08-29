@php
    use App\Services\Theme\ThemeSettingsService;

    $themeService = app(ThemeSettingsService::class);
    $settings = $themeService->settings();
    $vendorProfile = auth()->user()?->vendorProfile;

    $navItems = [
        ['route' => 'vendor.dashboard', 'label' => 'Dashboard'],
        ['route' => 'vendor.products.index', 'label' => 'Products'],
        ['route' => 'vendor.orders.index', 'label' => 'Orders'],
        ['route' => 'vendor.reviews.index', 'label' => 'Reviews'],
        ['route' => 'vendor.earnings', 'label' => 'Earnings'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ isset($title) ? $title.' - Vendor Dashboard' : 'Vendor Dashboard' }} - {{ $settings->site_name }}</title>

        {{-- This entire area is an authenticated back-office -- never indexable. --}}
        <meta name="robots" content="noindex, nofollow">

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
                <div class="flex items-start justify-between gap-2 border-b border-gray-100 px-5 py-5">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Vendor Dashboard</p>
                        <p class="mt-1 truncate text-sm font-semibold text-gray-900">{{ $vendorProfile?->shop_name ?? $settings->site_name }}</p>
                    </div>

                    <livewire:shared.notification-bell />
                </div>

                <nav class="flex-1 space-y-1 px-3 py-4">
                    @foreach ($navItems as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
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
                    <a href="{{ url('/') }}" wire:navigate class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900">
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
                <header class="border-b border-gray-200 bg-white lg:hidden" x-data="{ mobileNavOpen: false }">
                    <div class="flex h-14 items-center justify-between gap-4 px-4">
                        <span class="truncate text-sm font-semibold text-primary">{{ $vendorProfile?->shop_name ?? 'Vendor Dashboard' }}</span>

                        <div class="flex items-center gap-1">
                            <livewire:shared.notification-bell />

                            <button
                                type="button"
                                id="vendor-mobile-nav-toggle"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-700 transition-colors hover:bg-gray-100"
                                x-on:click="mobileNavOpen = ! mobileNavOpen"
                                :aria-expanded="mobileNavOpen.toString()"
                                aria-controls="vendor-mobile-nav-panel"
                                aria-label="Toggle navigation menu"
                            >
                                <svg x-show="! mobileNavOpen" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                                </svg>
                                <svg x-cloak x-show="mobileNavOpen" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <nav
                        id="vendor-mobile-nav-panel"
                        x-cloak
                        x-show="mobileNavOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="flex flex-col gap-1 border-t border-gray-100 px-3 py-3"
                    >
                        @foreach ($navItems as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                wire:navigate
                                @class([
                                    'block rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    'bg-primary/10 text-primary' => request()->routeIs($item['route'].'*'),
                                    'text-gray-700 hover:bg-gray-100' => ! request()->routeIs($item['route'].'*'),
                                ])
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                        <a href="{{ url('/') }}" wire:navigate class="block rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100">
                            Storefront
                        </a>
                    </nav>
                </header>

                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        <x-ui.toast-container />
    </body>
</html>
