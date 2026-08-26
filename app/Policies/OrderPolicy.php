<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Platform admins can do anything; this short-circuits every other check.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    /**
     * Determine whether the user can view their own order list (customer)
     * or their vendor sub-orders (vendor). Actual row scoping happens in
     * the query, this only gates whether the screen is reachable at all.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('customer') || $user->hasRole('vendor');
    }

    /**
     * Determine whether the user can view the order: the customer who
     * placed it, or a vendor who has at least one item in it.
     */
    public function view(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return true;
        }

        $vendorProfile = $user->vendorProfile;

        return $vendorProfile !== null
            && $order->items()->where('vendor_id', $vendorProfile->id)->exists();
    }

    /**
     * Any authenticated customer can place an order via checkout.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('customer');
    }

    /**
     * Order-level fields are never directly updated by customers or
     * vendors; vendors update their own OrderItem::vendor_status instead
     * (see OrderItem/RecalculateOrderStatusAction), which admins can also
     * override via the Filament panel (covered by the before() bypass).
     */
    public function update(User $user, Order $order): bool
    {
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
