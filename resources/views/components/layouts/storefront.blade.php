@php
    use App\Services\Theme\ThemeSettingsService;
    use Illuminate\Support\Facades\Storage;

    $themeService = app(ThemeSettingsService::class);
    $settings = $themeService->settings();

    $logoUrl = $settings->logo_path ? Storage::disk('public')->url($settings->logo_path) : null;
    $faviconUrl = $settings->favicon_path ? Storage::disk('public')->url($settings->favicon_path) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ isset($title) ? $title.' - '.$settings->site_name : $settings->site_name }}</title>

        @if ($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        @endif

        {{-- Runtime theme: rendered fresh on every request from SiteSetting via
             ThemeSettingsService, so admin edits apply without a rebuild. --}}
        <style>{!! $themeService->cssVariables() !!}</style>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-gray-900 antialiased">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-white">
            Skip to content
        </a>

        <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur" x-data="{ mobileNavOpen: false }">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" wire:navigate class="flex shrink-0 items-center gap-2">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $settings->site_name }}" class="h-9 w-auto">
                    @else
                        <span class="text-xl font-semibold tracking-tight text-primary">{{ $settings->site_name }}</span>
                    @endif
                </a>

                <div class="flex items-center gap-2 sm:gap-4">
                    {{-- Cart indicator and wishlist link stay visible at every breakpoint — never tucked inside the collapsed mobile menu. --}}
                    @auth
                        <a
                            href="{{ route('wishlist') }}"
                            wire:navigate
                            aria-label="Wishlist"
                            class="flex h-10 w-10 items-center justify-center rounded-md text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900"
                        >
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c-.3 0-.6-.1-.83-.29C7.9 17.24 3 13.06 3 8.99 3 6.24 5.15 4 7.8 4c1.5 0 2.95.72 3.9 1.87.05.06.15.06.2 0C12.85 4.72 14.3 4 15.8 4 18.45 4 20.6 6.24 20.6 8.99c0 4.07-4.9 8.25-8.17 10.97-.23.19-.53.29-.83.29z" />
                            </svg>
                        </a>

                        <livewire:shared.cart-indicator />

                        <livewire:shared.notification-bell />
                    @endauth

                    <nav class="hidden items-center gap-2 sm:gap-4 lg:flex">
                        @auth
                            <div class="group relative">
                                <button type="button" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900">
                                    {{ auth()->user()->name }}
                                </button>
                                <div class="invisible absolute right-0 z-10 mt-1 w-40 rounded-md border border-gray-200 bg-white py-1 opacity-0 shadow-lg transition-all group-hover:visible group-hover:opacity-100">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a
                                href="{{ route('login') }}"
                                wire:navigate
                                class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900"
                            >
                                Login
                            </a>
                        @endauth
                        @if (! auth()->check() || auth()->user()->vendorProfile === null)
                            <a
                                href="{{ route('vendor.apply') }}"
                                wire:navigate
                                class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:opacity-90 active:translate-y-0"
                            >
                                Become a Vendor
                            </a>
                        @else
                            <a
                                href="{{ route('vendor.dashboard') }}"
                                wire:navigate
                                class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:opacity-90 active:translate-y-0"
                            >
                                Vendor Dashboard
                            </a>
                        @endif
                    </nav>

                    <button
                        type="button"
                        id="mobile-nav-toggle"
                        class="inline-flex items-center justify-center rounded-md p-2 text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900 lg:hidden"
                        x-on:click="mobileNavOpen = ! mobileNavOpen"
                        :aria-expanded="mobileNavOpen.toString()"
                        aria-controls="mobile-nav-panel"
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

            {{-- Mobile nav panel: same links as the desktop nav, stacked, revealed by the hamburger toggle above `lg:`. --}}
            <div
                id="mobile-nav-panel"
                x-cloak
                x-show="mobileNavOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="border-t border-gray-200 bg-white px-4 py-4 sm:px-6 lg:hidden"
            >
                <nav class="flex flex-col gap-1">
                    @auth
                        <span class="px-3 py-2 text-sm font-medium text-gray-500">{{ auth()->user()->name }}</span>
                        <a
                            href="{{ route('wishlist') }}"
                            wire:navigate
                            class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900"
                        >
                            Wishlist
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900">
                                Logout
                            </button>
                        </form>
                    @else
                        <a
                            href="{{ route('login') }}"
                            wire:navigate
                            class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-gray-900"
                        >
                            Login
                        </a>
                    @endauth
                    @if (! auth()->check() || auth()->user()->vendorProfile === null)
                        <a
                            href="{{ route('vendor.apply') }}"
                            wire:navigate
                            class="mt-1 rounded-md bg-primary px-3 py-2 text-center text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90"
                        >
                            Become a Vendor
                        </a>
                    @else
                        <a
                            href="{{ route('vendor.dashboard') }}"
                            wire:navigate
                            class="mt-1 rounded-md bg-primary px-3 py-2 text-center text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90"
                        >
                            Vendor Dashboard
                        </a>
                    @endif
                </nav>
            </div>
        </header>

        <main id="main-content" class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-gray-200 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-lg font-semibold text-gray-900">{{ $settings->site_name }}</p>
                        <div class="mt-2 space-y-1 text-sm text-gray-600">
                            @if ($settings->contact_email)
                                <p>
                                    <a href="mailto:{{ $settings->contact_email }}" class="hover:text-primary">{{ $settings->contact_email }}</a>
                                </p>
                            @endif
                            @if ($settings->contact_phone)
                                <p>
                                    <a href="tel:{{ $settings->contact_phone }}" class="hover:text-primary">{{ $settings->contact_phone }}</a>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <p class="mt-8 text-sm text-gray-500">
                    &copy; {{ now()->year }} {{ $settings->site_name }}. All rights reserved.
                </p>
            </div>
        </footer>

        <x-ui.toast-container />
    </body>
</html>
