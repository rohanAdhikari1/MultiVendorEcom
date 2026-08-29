<?php

namespace App\Livewire\Vendor;

use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.vendor')]
class ReviewManager extends Component
{
    use WithPagination;

    public ?int $replyingToId = null;

    public string $replyBody = '';

    /**
     * Opens the reply form for one review, prefilled with the existing
     * reply text if the vendor is editing rather than replying fresh.
     */
    public function startReply(int $reviewId): void
    {
        $review = $this->ownedVisibleReview($reviewId);

        $this->replyingToId = $reviewId;
        $this->replyBody = $review->vendor_reply ?? '';
        $this->resetErrorBag('replyBody');
    }

    public function cancelReply(): void
    {
        $this->replyingToId = null;
        $this->replyBody = '';
        $this->resetErrorBag('replyBody');
    }

    public function saveReply(int $reviewId): void
    {
        $review = $this->ownedVisibleReview($reviewId);

        $this->validate([
            'replyBody' => ['required', 'string', 'max:2000'],
        ]);

        $review->update([
            'vendor_reply' => $this->replyBody,
            'vendor_replied_at' => now(),
        ]);

        $this->replyingToId = null;
        $this->replyBody = '';

        $this->dispatch('notify', message: 'Reply saved.', type: 'success');
    }

    /**
     * Loads the review and verifies it belongs to one of THIS vendor's
     * products. ReviewPolicy::view() grants everyone visibility into
     * reviews (they're public on the product page), so it doesn't prove
     * the review is on this vendor's own product -- that ownership check
     * happens here explicitly, mirroring OrderManager::updateStatus's
     * "policy grants broad access, an inline check narrows to the specific
     * owned sub-resource" pattern. A review an admin has hidden
     * (is_visible = false) is excluded from the list query below, but a
     * vendor could still try to target its id directly via a component
     * call, so that's re-checked here too.
     */
    protected function ownedVisibleReview(int $reviewId): Review
    {
        $vendorProfile = $this->vendorProfile();

        $review = Review::query()->with('product')->findOrFail($reviewId);

        abort_unless($review->product->vendor_id === $vendorProfile->id, 403);
        abort_unless($review->is_visible, 403);

        return $review;
    }

    public function render(): View
    {
        $vendorProfile = $this->vendorProfile();

        $reviews = Review::query()
            ->whereHas('product', fn ($query) => $query->where('vendor_id', $vendorProfile->id))
            ->where('is_visible', true)
            ->with(['product', 'user'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.vendor.review-manager', [
            'reviews' => $reviews,
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
