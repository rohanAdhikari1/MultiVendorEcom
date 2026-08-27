<?php

namespace App\Livewire\Vendor\ProductManager;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Handles both create (no route-bound product) and edit (route-bound
 * product) for a vendor's own products. is_featured is deliberately never
 * exposed here — that toggle is admin-only (see ProductForm in Filament).
 */
#[Layout('components.layouts.vendor')]
class Form extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public string $slug = '';

    public bool $slugManuallyEdited = false;

    public ?int $categoryId = null;

    public string $description = '';

    public string $sku = '';

    public string $price = '';

    public string $compareAtPrice = '';

    public string $stockQuantity = '0';

    public string $status = 'draft';

    /** @var array<int, TemporaryUploadedFile> */
    public array $newImages = [];

    /**
     * Each row: ['id' => ?int, 'sku' => string, 'size' => string, 'color' => string, 'priceOverride' => string, 'stockQuantity' => string].
     *
     * @var array<int, array<string, mixed>>
     */
    public array $variantRows = [];

    public function mount(?Product $product = null): void
    {
        if ($product !== null && $product->exists) {
            $this->authorize('update', $product);

            $product->load(['images', 'variants']);
            $this->product = $product;

            $this->name = $product->name;
            $this->slug = $product->slug;
            $this->slugManuallyEdited = true;
            $this->categoryId = $product->category_id;
            $this->description = (string) $product->description;
            $this->sku = (string) $product->sku;
            $this->price = (string) $product->price;
            $this->compareAtPrice = $product->compare_at_price !== null ? (string) $product->compare_at_price : '';
            $this->stockQuantity = (string) $product->stock_quantity;
            $this->status = $product->status->value;

            $this->variantRows = $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => (string) $variant->sku,
                'size' => (string) ($variant->attributes['size'] ?? ''),
                'color' => (string) ($variant->attributes['color'] ?? ''),
                'priceOverride' => $variant->price_override !== null ? (string) $variant->price_override : '',
                'stockQuantity' => (string) $variant->stock_quantity,
            ])->all();
        } else {
            $this->authorize('create', Product::class);
        }
    }

    public function updatedName(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->slug = Str::slug($this->slug);
    }

    public function removeNewImage(int $index): void
    {
        unset($this->newImages[$index]);
        $this->newImages = array_values($this->newImages);
    }

    public function setPrimaryImage(int $imageId): void
    {
        if ($this->product === null) {
            return;
        }

        $this->authorize('update', $this->product);

        $this->product->images()->update(['is_primary' => false]);
        $this->product->images()->whereKey($imageId)->update(['is_primary' => true]);
    }

    public function deleteImage(int $imageId): void
    {
        if ($this->product === null) {
            return;
        }

        $this->authorize('update', $this->product);

        $image = $this->product->images()->whereKey($imageId)->first();

        if ($image === null) {
            return;
        }

        $wasPrimary = $image->is_primary;
        Storage::disk('public')->delete($image->path);
        $image->delete();

        if ($wasPrimary) {
            $this->product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }
    }

    public function moveImage(int $imageId, string $direction): void
    {
        if ($this->product === null) {
            return;
        }

        $this->authorize('update', $this->product);

        $images = $this->product->images()->orderBy('sort_order')->get();
        $index = $images->search(fn (ProductImage $image) => $image->id === $imageId);

        if ($index === false) {
            return;
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if (! $images->has($swapWith)) {
            return;
        }

        $a = $images->get($index);
        $b = $images->get($swapWith);

        [$aOrder, $bOrder] = [$a->sort_order, $b->sort_order];
        $a->update(['sort_order' => $bOrder]);
        $b->update(['sort_order' => $aOrder]);
    }

    public function addVariantRow(): void
    {
        $this->variantRows[] = [
            'id' => null,
            'sku' => '',
            'size' => '',
            'color' => '',
            'priceOverride' => '',
            'stockQuantity' => '',
        ];
    }

    public function removeVariantRow(int $index): void
    {
        unset($this->variantRows[$index]);
        $this->variantRows = array_values($this->variantRows);
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        DB::transaction(function () use ($validated): void {
            $attributes = [
                'category_id' => $validated['categoryId'],
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] !== '' ? $validated['description'] : null,
                'sku' => $validated['sku'] !== '' ? $validated['sku'] : null,
                'price' => $validated['price'],
                'compare_at_price' => $validated['compareAtPrice'] !== '' ? $validated['compareAtPrice'] : null,
                'stock_quantity' => (int) $validated['stockQuantity'],
                'status' => ProductStatus::from($validated['status']),
            ];

            if ($this->product === null) {
                $this->authorize('create', Product::class);

                $this->product = Product::query()->create($attributes + [
                    'vendor_id' => $this->vendorProfile()->id,
                    'is_featured' => false,
                ]);
            } else {
                $this->authorize('update', $this->product);

                $this->product->update($attributes);
            }

            $this->persistNewImages();
            $this->persistVariants();
        });

        session()->flash('status', 'Product saved.');

        $this->redirect(route('vendor.products.index'), navigate: false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('products', 'slug')->ignore($this->product?->id),
            ],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'compareAtPrice' => ['nullable', 'numeric', 'min:0'],
            'stockQuantity' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(array_map(fn (ProductStatus $case) => $case->value, ProductStatus::cases()))],
            'newImages.*' => ['image', 'max:4096'],
        ];
    }

    protected function persistNewImages(): void
    {
        if (empty($this->newImages) || $this->product === null) {
            return;
        }

        $nextSortOrder = (int) ($this->product->images()->max('sort_order') ?? -1) + 1;
        $hasPrimary = $this->product->images()->where('is_primary', true)->exists();

        foreach ($this->newImages as $file) {
            $path = $file->store('products', 'public');

            ProductImage::query()->create([
                'product_id' => $this->product->id,
                'path' => $path,
                'sort_order' => $nextSortOrder++,
                'is_primary' => ! $hasPrimary,
            ]);

            $hasPrimary = true;
        }

        $this->newImages = [];
    }

    protected function persistVariants(): void
    {
        if ($this->product === null) {
            return;
        }

        $keepIds = [];

        foreach ($this->variantRows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            $size = trim((string) ($row['size'] ?? ''));
            $color = trim((string) ($row['color'] ?? ''));
            $priceOverride = trim((string) ($row['priceOverride'] ?? ''));
            $stockQuantity = trim((string) ($row['stockQuantity'] ?? ''));

            if ($sku === '' && $size === '' && $color === '' && $priceOverride === '' && $stockQuantity === '') {
                continue;
            }

            $attributes = array_filter([
                'size' => $size !== '' ? $size : null,
                'color' => $color !== '' ? $color : null,
            ]);

            $data = [
                'product_id' => $this->product->id,
                'sku' => $sku !== '' ? $sku : null,
                'attributes' => $attributes,
                'price_override' => $priceOverride !== '' ? $priceOverride : null,
                'stock_quantity' => $stockQuantity !== '' ? (int) $stockQuantity : 0,
            ];

            $variantId = $row['id'] ?? null;
            $variant = $variantId !== null
                ? ProductVariant::query()->where('product_id', $this->product->id)->find((int) $variantId)
                : null;

            if ($variant !== null) {
                $variant->update($data);
            } else {
                $variant = ProductVariant::query()->create($data);
            }

            $keepIds[] = $variant->id;
        }

        $this->product->variants()->whereNotIn('id', $keepIds)->delete();
    }

    public function render(): View
    {
        return view('livewire.vendor.product-manager.form', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'existingImages' => $this->product?->images()->orderBy('sort_order')->get() ?? collect(),
            'statuses' => ProductStatus::cases(),
        ]);
    }

    protected function vendorProfile(): VendorProfile
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var VendorProfile $vendorProfile */
        $vendorProfile = $user->vendorProfile;

        return $vendorProfile;
    }
}
