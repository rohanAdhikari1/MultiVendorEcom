<?php

namespace App\Livewire\Vendor;

use App\Actions\Orders\RecalculateOrderStatusAction;
use App\Enums\VendorSubOrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.vendor')]
class OrderManager extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Updates one of THIS vendor's own OrderItem lines. OrderPolicy::view()
     * only proves the vendor has visibility into the order (they have at
     * least one item in it) — it does not prove the specific item being
     * mutated is theirs, since a multi-vendor order can carry other
     * vendors' items too. That ownership check happens here explicitly.
     */
    public function updateStatus(int $orderItemId, string $newStatus): void
    {
        $vendorProfile = $this->vendorProfile();

        $orderItem = OrderItem::query()->with('order')->findOrFail($orderItemId);

        $this->authorize('view', $orderItem->order);

        abort_unless($orderItem->vendor_id === $vendorProfile->id, 403);

        $newStatusEnum = VendorSubOrderStatus::from($newStatus);

        if (! in_array($newStatusEnum, $this->allowedNextStatuses($orderItem->vendor_status), true)) {
            return;
        }

        $orderItem->vendor_status = $newStatusEnum;
        $orderItem->save();

        app(RecalculateOrderStatusAction::class)->execute($orderItem->order);
    }

    /**
     * @return array<int, VendorSubOrderStatus>
     */
    public function allowedNextStatuses(VendorSubOrderStatus $current): array
    {
        return match ($current) {
            VendorSubOrderStatus::Pending => [VendorSubOrderStatus::Processing, VendorSubOrderStatus::Cancelled],
            VendorSubOrderStatus::Processing => [VendorSubOrderStatus::Shipped, VendorSubOrderStatus::Cancelled],
            VendorSubOrderStatus::Shipped => [VendorSubOrderStatus::Delivered],
            VendorSubOrderStatus::Delivered, VendorSubOrderStatus::Cancelled => [],
        };
    }

    public function render(): View
    {
        $vendorProfile = $this->vendorProfile();

        $orderIdsQuery = OrderItem::query()->where('vendor_id', $vendorProfile->id);

        if ($this->statusFilter !== '') {
            $orderIdsQuery->where('vendor_status', $this->statusFilter);
        }

        $orderIds = $orderIdsQuery->select('order_id')->distinct()->pluck('order_id');

        $orders = Order::query()
            ->whereIn('id', $orderIds)
            ->with(['user', 'items' => fn ($query) => $query->where('vendor_id', $vendorProfile->id)])
            ->orderByDesc('placed_at')
            ->paginate(10);

        return view('livewire.vendor.order-manager', [
            'orders' => $orders,
            'statuses' => VendorSubOrderStatus::cases(),
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
