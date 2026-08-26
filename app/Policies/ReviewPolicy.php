<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Platform admins can do anything; this short-circuits every other check.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    /**
     * Reviews are public (product page display), so anyone can view them.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Review $review): bool
    {
        return true;
    }

    /**
     * Any authenticated customer can attempt to leave a review. Whether
     * they've actually purchased the product / already reviewed it is
     * business logic enforced by the review-submission action, not here.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('customer');
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }
}
