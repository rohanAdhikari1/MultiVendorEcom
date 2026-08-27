<x-layouts.storefront :title="'Payment Failed'">
    <div class="mx-auto max-w-xl px-4 py-20 text-center sm:px-6 lg:px-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
            <svg viewBox="0 0 24 24" class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-gray-900">We couldn't confirm your payment</h1>

        <p class="mt-3 text-sm text-gray-600">
            {{ session('paymentError') ?? 'Your payment could not be confirmed. If any amount was deducted, it will be refunded automatically, or please contact support.' }}
        </p>

        @if (request()->query('order'))
            <p class="mt-1 text-sm text-gray-500">Order reference: {{ request()->query('order') }}</p>
        @endif

        <div class="mt-8 flex justify-center gap-3">
            <x-ui.button :href="route('cart')" variant="secondary">Back to Cart</x-ui.button>
            <x-ui.button :href="route('home')">Continue Shopping</x-ui.button>
        </div>
    </div>
</x-layouts.storefront>
