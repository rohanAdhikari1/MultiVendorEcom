<?php

namespace App\Livewire\Vendor\Onboarding;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ApplicationForm extends Component
{
    public string $shopName = '';

    public string $shopSlug = '';

    public bool $slugManuallyEdited = false;

    public string $description = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Someone who already applied (in any status) has nothing to fill
        // out here — send them to /vendor and let the approval middleware
        // route them to the dashboard or the appropriate holding page.
        if ($user->vendorProfile !== null) {
            $this->redirect(route('vendor.dashboard'), navigate: false);
        }
    }

    public function updatedShopName(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->shopSlug = Str::slug($this->shopName);
        }
    }

    public function updatedShopSlug(): void
    {
        $this->slugManuallyEdited = true;
        $this->shopSlug = Str::slug($this->shopSlug);
    }

    public function apply(): void
    {
        $this->authorize('create', VendorProfile::class);

        $validated = $this->validate([
            'shopName' => ['required', 'string', 'max:255'],
            'shopSlug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:vendor_profiles,shop_slug'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'shopName' => 'shop name',
            'shopSlug' => 'shop URL',
        ]);

        /** @var User $user */
        $user = Auth::user();

        VendorProfile::query()->create([
            'user_id' => $user->id,
            'shop_name' => $validated['shopName'],
            'shop_slug' => $validated['shopSlug'],
            'description' => $validated['description'] ?: null,
            'status' => VendorApplicationStatus::Pending,
        ]);

        if (! $user->hasRole('vendor')) {
            $user->assignRole('vendor');
        }

        $this->redirect(route('vendor.dashboard'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.vendor.onboarding.application-form');
    }
}
