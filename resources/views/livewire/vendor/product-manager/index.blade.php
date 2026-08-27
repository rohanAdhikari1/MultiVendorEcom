@php
    use App\Enums\ProductStatus;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Number;
@endphp

<div class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Products</h1>
        <x-ui.button :href="route('vendor.products.create')">Add product</x-ui.button>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if ($products->isEmpty())
        <div class="mt-8">
            <x-ui.empty-state title="No products yet" message="Add your first product to start selling.">
                <x-ui.button :href="route('vendor.products.create')">Add product</x-ui.button>
            </x-ui.empty-state>
        </div>
    @else
        <div class="mt-6 overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500"></th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Category</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Price</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Stock</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Featured</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($products as $product)
                        @php
                            $statusVariant = match ($product->status) {
                                ProductStatus::Published => 'success',
                                ProductStatus::Archived => 'neutral',
                                ProductStatus::Draft => 'warning',
                            };
                        @endphp
                        <tr wire:key="product-{{ $product->id }}">
                            <td class="px-4 py-3">
                                <div class="h-10 w-10 overflow-hidden rounded bg-gray-100">
                                    @if ($product->primaryImage)
                                        <img src="{{ Storage::disk('public')->url($product->primaryImage->path) }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $product->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ Number::currency((float) $product->price, 'NPR') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $product->stock_quantity }}</td>
                            <td class="px-4 py-3"><x-ui.badge :variant="$statusVariant">{{ $product->status->label() }}</x-ui.badge></td>
                            <td class="px-4 py-3 text-gray-600">{{ $product->is_featured ? 'Yes' : '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('vendor.products.edit', $product) }}" class="font-medium text-primary hover:underline">Edit</a>
                                <button type="button" wire:click="confirmDelete({{ $product->id }})" class="ml-3 font-medium text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif

    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">Delete this product?</h2>
                <p class="mt-1 text-sm text-gray-600">This cannot be undone.</p>
                <div class="mt-5 flex justify-end gap-3">
                    <x-ui.button variant="secondary" wire:click="cancelDelete" wire:loading.attr="disabled" wire:target="delete">Cancel</x-ui.button>
                    <x-ui.button variant="primary" wire:click="delete({{ $confirmingDeleteId }})" wire:loading.attr="disabled" wire:target="delete" class="bg-red-600!">
                        <span wire:loading.remove wire:target="delete">Delete</span>
                        <span wire:loading wire:target="delete">Deleting&hellip;</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
