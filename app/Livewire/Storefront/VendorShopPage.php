<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.storefront')]
class VendorShopPage extends Component
{
    use WithPagination;

    public VendorProfile $vendor;

    public function mount(string $slug): void
    {
        $this->vendor = VendorProfile::query()
            ->where('shop_slug', $slug)
            ->where('status', VendorApplicationStatus::Approved)
            ->firstOrFail();
    }

    public function render(): View
    {
        $products = $this->vendor->products()
            ->with(['vendor', 'primaryImage'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('status', ProductStatus::Published)
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('livewire.storefront.vendor-shop-page', [
            'products' => $products,
        ]);
    }
}
