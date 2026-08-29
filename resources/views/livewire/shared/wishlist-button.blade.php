<div>
    @auth
        <button
            type="button"
            wire:click.stop.prevent="toggle"
            wire:loading.attr="disabled"
            wire:target="toggle"
            aria-pressed="{{ $isWishlisted ? 'true' : 'false' }}"
            aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-500 shadow-sm backdrop-blur transition-all duration-150 ease-out hover:scale-110 hover:text-red-500 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60"
        >
            <svg
                viewBox="0 0 24 24"
                class="h-5 w-5 transition-transform duration-200 ease-out {{ $isWishlisted ? 'scale-110 text-red-500' : 'text-gray-500' }}"
                fill="{{ $isWishlisted ? 'currentColor' : 'none' }}"
                stroke="currentColor"
                stroke-width="1.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c-.3 0-.6-.1-.83-.29C7.9 17.24 3 13.06 3 8.99 3 6.24 5.15 4 7.8 4c1.5 0 2.95.72 3.9 1.87.05.06.15.06.2 0C12.85 4.72 14.3 4 15.8 4 18.45 4 20.6 6.24 20.6 8.99c0 4.07-4.9 8.25-8.17 10.97-.23.19-.53.29-.83.29z" />
            </svg>
        </button>
    @else
        <a
            href="{{ route('login') }}"
            wire:navigate
            aria-label="Log in to add to wishlist"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 text-gray-500 shadow-sm backdrop-blur transition-all duration-150 ease-out hover:scale-110 hover:text-red-500 active:scale-95"
        >
            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c-.3 0-.6-.1-.83-.29C7.9 17.24 3 13.06 3 8.99 3 6.24 5.15 4 7.8 4c1.5 0 2.95.72 3.9 1.87.05.06.15.06.2 0C12.85 4.72 14.3 4 15.8 4 18.45 4 20.6 6.24 20.6 8.99c0 4.07-4.9 8.25-8.17 10.97-.23.19-.53.29-.83.29z" />
            </svg>
        </a>
    @endauth
</div>
