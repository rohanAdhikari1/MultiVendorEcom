@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">
        {{ $product ? 'Edit product' : 'Add product' }}
    </h1>

    <form wire:submit="save" class="mt-6 space-y-8">
        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-base font-semibold text-gray-900">Basic details</h2>

            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                        type="text"
                        id="name"
                        wire:model.blur="name"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                    <input
                        type="text"
                        id="slug"
                        wire:model.blur="slug"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    @error('slug')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="categoryId" class="block text-sm font-medium text-gray-700">Category</label>
                    <select
                        id="categoryId"
                        wire:model="categoryId"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Select a category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                    <input
                        type="text"
                        id="sku"
                        wire:model="sku"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    @error('sku')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
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
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-base font-semibold text-gray-900">Pricing &amp; inventory</h2>

            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700">Price (NPR)</label>
                    <input
                        type="number" step="0.01" min="0"
                        id="price"
                        wire:model="price"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    @error('price')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="compareAtPrice" class="block text-sm font-medium text-gray-700">Compare-at price</label>
                    <input
                        type="number" step="0.01" min="0"
                        id="compareAtPrice"
                        wire:model="compareAtPrice"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    @error('compareAtPrice')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="stockQuantity" class="block text-sm font-medium text-gray-700">Stock quantity</label>
                    <input
                        type="number" step="1" min="0"
                        id="stockQuantity"
                        wire:model="stockQuantity"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                    @error('stockQuantity')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                        id="status"
                        wire:model="status"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        @foreach ($statuses as $statusCase)
                            <option value="{{ $statusCase->value }}">{{ $statusCase->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-base font-semibold text-gray-900">Images</h2>

            @if ($product && $existingImages->isNotEmpty())
                <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                    @foreach ($existingImages as $image)
                        <div wire:key="existing-image-{{ $image->id }}" class="relative overflow-hidden rounded-lg border border-gray-200">
                            <img src="{{ Storage::disk('public')->url($image->path) }}" alt="" class="aspect-square w-full object-cover">
                            @if ($image->is_primary)
                                <span class="absolute left-1 top-1"><x-ui.badge variant="primary">Primary</x-ui.badge></span>
                            @endif
                            <div class="flex items-center justify-between gap-1 bg-white px-1.5 py-1 text-xs">
                                <button type="button" wire:click="moveImage({{ $image->id }}, 'up')" class="text-gray-500 hover:text-gray-900">&uarr;</button>
                                <button type="button" wire:click="moveImage({{ $image->id }}, 'down')" class="text-gray-500 hover:text-gray-900">&darr;</button>
                                @unless ($image->is_primary)
                                    <button type="button" wire:click="setPrimaryImage({{ $image->id }})" class="text-primary hover:underline">Set primary</button>
                                @endunless
                                <button type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="Delete this image?" class="text-red-600 hover:underline">Delete</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-4">
                <label for="newImages" class="block text-sm font-medium text-gray-700">Add images</label>
                <input
                    type="file"
                    id="newImages"
                    wire:model="newImages"
                    multiple
                    accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20"
                >
                @error('newImages.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                @if (!empty($newImages))
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach ($newImages as $index => $file)
                            <div wire:key="new-image-{{ $index }}" class="relative">
                                <img src="{{ $file->temporaryUrl() }}" alt="" class="h-20 w-20 rounded-lg border border-gray-200 object-cover">
                                <button type="button" wire:click="removeNewImage({{ $index }})" class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-white text-xs text-red-600 shadow">&times;</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900">Variants <span class="font-normal text-gray-500">(optional)</span></h2>
                <button type="button" wire:click="addVariantRow" class="text-sm font-medium text-primary hover:underline">+ Add variant</button>
            </div>

            @if (!empty($variantRows))
                <div class="mt-4 space-y-3">
                    @foreach ($variantRows as $index => $row)
                        <div wire:key="variant-row-{{ $index }}" class="grid grid-cols-2 gap-3 rounded-md border border-gray-100 p-3 sm:grid-cols-6">
                            <input type="text" wire:model="variantRows.{{ $index }}.size" placeholder="Size" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                            <input type="text" wire:model="variantRows.{{ $index }}.color" placeholder="Color" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                            <input type="text" wire:model="variantRows.{{ $index }}.sku" placeholder="SKU" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                            <input type="number" step="0.01" min="0" wire:model="variantRows.{{ $index }}.priceOverride" placeholder="Price override" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                            <input type="number" step="1" min="0" wire:model="variantRows.{{ $index }}.stockQuantity" placeholder="Stock" class="rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                            <button type="button" wire:click="removeVariantRow({{ $index }})" class="text-sm font-medium text-red-600 hover:underline">Remove</button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-sm text-gray-500">No variants — this product will be sold as a single item.</p>
            @endif
        </div>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button :href="route('vendor.products.index')" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save product</span>
                <span wire:loading wire:target="save">Saving&hellip;</span>
            </x-ui.button>
        </div>
    </form>
</div>
