<?php

namespace App\Policies;

use App\Enums\VendorApplicationStatus;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Platform admins can do anything; this short-circuits every other check.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    /**
     * Determine whether the user can view the vendor-facing product list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('vendor');
    }

    /**
     * Determine whether the user can view the model in the vendor dashboard.
     */
    public function view(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * Determine whether the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('vendor')
            && $user->vendorProfile?->status === VendorApplicationStatus::Approved;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->ownsProduct($user, $product);
    }

    protected function ownsProduct(User $user, Product $product): bool
    {
        return $user->vendorProfile !== null
            && $user->vendorProfile->id === $product->vendor_id;
    }
}
