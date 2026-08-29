<?php

namespace App\Livewire\Vendor;

use App\Enums\PayoutStatus;
use App\Enums\VendorSubOrderStatus;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorPayout;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.vendor')]
class Earnings extends Component
{
    public function mount(): void
    {
        $this->authorize('view', $this->vendorProfile());
    }

    public function render(): View
    {
        $vendorProfile = $this->vendorProfile();

        // Everything ever earned on a Delivered item, whether or not it has
        // since been folded into a payout batch.
        $totalLifetimeEarnings = (float) OrderItem::query()
            ->where('vendor_id', $vendorProfile->id)
            ->where('vendor_status', VendorSubOrderStatus::Delivered)
            ->sum('vendor_earning');

        $totalPaidOut = (float) VendorPayout::query()
            ->where('vendor_id', $vendorProfile->id)
            ->where('status', PayoutStatus::Paid)
            ->sum('total_earnings');

        // Same eligibility shape as CreateVendorPayoutBatchAction: Delivered
        // and not yet attached to any payout batch.
        $pendingPayout = (float) OrderItem::query()
            ->where('vendor_id', $vendorProfile->id)
            ->where('vendor_status', VendorSubOrderStatus::Delivered)
            ->whereNull('payout_id')
            ->sum('vendor_earning');

        $payouts = VendorPayout::query()
            ->where('vendor_id', $vendorProfile->id)
            ->orderByDesc('period_end')
            ->get();

        return view('livewire.vendor.earnings', [
            'vendorProfile' => $vendorProfile,
            'totalLifetimeEarnings' => $totalLifetimeEarnings,
            'totalPaidOut' => $totalPaidOut,
            'pendingPayout' => $pendingPayout,
            'payouts' => $payouts,
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
