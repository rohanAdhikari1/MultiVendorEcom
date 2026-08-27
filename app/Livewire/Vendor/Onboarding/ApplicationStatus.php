<?php

namespace App\Livewire\Vendor\Onboarding;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Holding page shown for a user whose VendorProfile exists but isn't
 * Approved yet (Pending/Rejected/Suspended). Reached only via the
 * EnsureVendorApproved middleware redirect, or directly by URL — mount()
 * sends an unapplied user to /become-a-vendor and an already-approved
 * vendor straight to their dashboard so this page never shows the wrong
 * thing.
 */
#[Layout('components.layouts.storefront')]
class ApplicationStatus extends Component
{
    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $vendorProfile = $user->vendorProfile;

        if ($vendorProfile === null) {
            $this->redirect(route('vendor.apply'), navigate: false);

            return;
        }

        if ($vendorProfile->status === VendorApplicationStatus::Approved) {
            $this->redirect(route('vendor.dashboard'), navigate: false);
        }
    }

    public function render(): View
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var VendorProfile $vendorProfile */
        $vendorProfile = $user->vendorProfile;

        return view('livewire.vendor.onboarding.application-status', [
            'vendorProfile' => $vendorProfile,
        ]);
    }
}
