<?php

namespace App\Livewire\Vendor\ProductManager;

use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.vendor')]
class Index extends Component
{
    use WithPagination;

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Product::class);
    }

    public function confirmDelete(int $productId): void
    {
        $this->confirmingDeleteId = $productId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(int $productId): void
    {
        // Deliberately not scoped to this vendor's products in the query —
        // ownership is enforced by the policy so a cross-vendor attempt
        // fails with an explicit 403 rather than a 404.
        $product = Product::query()->findOrFail($productId);

        $this->authorize('delete', $product);

        $product->delete();

        $this->confirmingDeleteId = null;

        $this->dispatch('notify', message: 'Product deleted.', type: 'success');
    }

    public function render(): View
    {
        $products = Product::query()
            ->where('vendor_id', $this->vendorProfile()->id)
            ->with(['category', 'primaryImage'])
            ->latest()
            ->paginate(10);

        return view('livewire.vendor.product-manager.index', [
            'products' => $products,
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
