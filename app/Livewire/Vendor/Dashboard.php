<?php

namespace App\Livewire\Vendor;

use App\Enums\ProductStatus;
use App\Enums\VendorSubOrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.vendor')]
class Dashboard extends Component
{
    public function mount(): void
    {
        $this->authorize('view', $this->vendorProfile());
    }

    public function render(): View
    {
        $vendorProfile = $this->vendorProfile();

        $totalOrders = Order::query()
            ->whereHas('items', fn ($query) => $query->where('vendor_id', $vendorProfile->id))
            ->count();

        $totalRevenue = (float) OrderItem::query()
            ->where('vendor_id', $vendorProfile->id)
            ->sum('vendor_earning');

        $pendingFulfillmentCount = OrderItem::query()
            ->where('vendor_id', $vendorProfile->id)
            ->whereIn('vendor_status', [VendorSubOrderStatus::Pending, VendorSubOrderStatus::Processing])
            ->count();

        $publishedProductCount = Product::query()
            ->where('vendor_id', $vendorProfile->id)
            ->where('status', ProductStatus::Published)
            ->count();

        $recentItems = OrderItem::query()
            ->where('vendor_id', $vendorProfile->id)
            ->with('order')
            ->latest()
            ->take(10)
            ->get();

        return view('livewire.vendor.dashboard', [
            'vendorProfile' => $vendorProfile,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'pendingFulfillmentCount' => $pendingFulfillmentCount,
            'publishedProductCount' => $publishedProductCount,
            'recentItems' => $recentItems,
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
