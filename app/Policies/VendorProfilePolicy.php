<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorProfile;

class VendorProfilePolicy
{
    /**
     * Platform admins can do anything; this short-circuits every other check.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    /**
     * Determine whether the user can view their own vendor profile/dashboard.
     */
    public function view(User $user, VendorProfile $vendorProfile): bool
    {
        return $vendorProfile->user_id === $user->id;
    }

    /**
     * Any authenticated user without an existing vendor profile can submit
     * a "become a vendor" application. The "no existing profile yet" part
     * is enforced by the application form/action, not here.
     */
    public function create(User $user): bool
    {
        return $user->vendorProfile === null;
    }

    /**
     * Determine whether the user can update their own shop branding/details.
     */
    public function update(User $user, VendorProfile $vendorProfile): bool
    {
        return $vendorProfile->user_id === $user->id;
    }

    public function delete(User $user, VendorProfile $vendorProfile): bool
    {
        return false;
    }
}
