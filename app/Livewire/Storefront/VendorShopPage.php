<?php

namespace App\Livewire\Storefront;

use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Models\VendorProfile;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
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

        $ogImagePath = $this->vendor->logo_path ?? $this->vendor->banner_path;

        return view('livewire.storefront.vendor-shop-page', [
            'products' => $products,
        ])->layoutData([
            'title' => $this->vendor->shop_name,
            'description' => Seo::excerpt($this->vendor->description),
            'ogImage' => $ogImagePath ? Storage::disk('public')->url($ogImagePath) : null,
        ]);
    }
}
