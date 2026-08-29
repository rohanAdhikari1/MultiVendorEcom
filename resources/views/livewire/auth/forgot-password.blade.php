<div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Forgot your password?</h1>
        <p class="mt-1 text-sm text-gray-600">Enter your email and we'll send you a link to reset it.</p>

        @if ($submitted)
            <div class="mt-6 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                If that email address is in our system, a password reset link has been sent to it.
            </div>

            <p class="mt-6 text-center text-sm text-gray-600">
                <a href="{{ route('login') }}" class="font-medium text-primary hover:opacity-90">Back to log in</a>
            </p>
        @else
            <form wire:submit="sendResetLink" class="mt-6 space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        autocomplete="email"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:opacity-90 active:scale-95 disabled:cursor-wait disabled:opacity-75 disabled:active:scale-100"
                    wire:loading.attr="disabled"
                    wire:target="sendResetLink"
                >
                    Send password reset link
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                Remembered your password?
                <a href="{{ route('login') }}" class="font-medium text-primary hover:opacity-90">Log in</a>
            </p>
        @endif
    </div>
</div>
