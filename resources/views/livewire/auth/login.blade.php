<div class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Log in</h1>
        <p class="mt-1 text-sm text-gray-600">Welcome back, log in to your account.</p>

        <form wire:submit="login" class="mt-6 space-y-5">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    autocomplete="email"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    autocomplete="current-password"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="remember"
                    wire:model="remember"
                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                >
                <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90 disabled:cursor-wait disabled:opacity-75"
                wire:loading.attr="disabled"
                wire:target="login"
            >
                Log in
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-medium text-primary hover:opacity-90">Register</a>
        </p>
    </div>
</div>
