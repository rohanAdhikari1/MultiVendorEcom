<div class="mx-auto flex min-h-[70vh] max-w-xl flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Become a vendor</h1>
        <p class="mt-1 text-sm text-gray-600">Tell us about your shop. An admin will review your application before you can start listing products.</p>

        <form wire:submit="apply" class="mt-6 space-y-5">
            <div>
                <label for="shopName" class="block text-sm font-medium text-gray-700">Shop name</label>
                <input
                    type="text"
                    id="shopName"
                    wire:model.blur="shopName"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                @error('shopName')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="shopSlug" class="block text-sm font-medium text-gray-700">Shop URL</label>
                <div class="mt-1 flex items-center rounded-md border border-gray-300 shadow-sm focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
                    <span class="pl-3 text-sm text-gray-400">{{ url('/store') }}/</span>
                    <input
                        type="text"
                        id="shopSlug"
                        wire:model.blur="shopSlug"
                        class="block w-full border-0 bg-transparent px-1 py-2 text-sm focus:outline-none focus:ring-0"
                    >
                </div>
                @error('shopSlug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Shop description</label>
                <textarea
                    id="description"
                    wire:model="description"
                    rows="4"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                ></textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:opacity-90"
                wire:loading.attr="disabled"
                wire:target="apply"
            >
                Submit application
            </button>
        </form>
    </div>
</div>
