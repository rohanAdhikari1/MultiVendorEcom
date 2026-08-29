<div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Reset your password</h1>
        <p class="mt-1 text-sm text-gray-600">Choose a new password for your account.</p>

        <form wire:submit="resetPassword" class="mt-6 space-y-5">
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

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New password</label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    autocomplete="new-password"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    wire:model="password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm transition duration-150 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:opacity-90 active:scale-95 disabled:cursor-wait disabled:opacity-75 disabled:active:scale-100"
                wire:loading.attr="disabled"
                wire:target="resetPassword"
            >
                Reset password
            </button>
        </form>
    </div>
</div>
