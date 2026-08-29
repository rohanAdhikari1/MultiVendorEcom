<?php

namespace App\Actions\Payouts;

use App\Enums\PayoutStatus;
use App\Enums\VendorSubOrderStatus;
use App\Models\OrderItem;
use App\Models\VendorPayout;
use App\Models\VendorProfile;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Batches up a vendor's outstanding earnings into a single VendorPayout.
 *
 * Only OrderItems that have actually been Delivered and are not already
 * attached to a prior payout are eligible — an item that is merely Shipped
 * (or Cancelled, or already paid out) is left untouched. Payout creation
 * and the OrderItem::payout_id assignment happen inside one transaction so
 * a failure partway through never leaves a payout row with no items or
 * items silently unassigned.
 */
class CreateVendorPayoutBatchAction
{
    /**
     * @param  CarbonInterface|null  $periodStart  Inclusive lower bound on OrderItem::created_at. Omit to include everything eligible to date.
     * @param  CarbonInterface|null  $periodEnd  Inclusive upper bound on OrderItem::created_at. Omit to include everything eligible to date.
     * @return VendorPayout|null The created payout, or null when there is nothing eligible to pay out.
     */
    public function execute(VendorProfile $vendor, ?CarbonInterface $periodStart = null, ?CarbonInterface $periodEnd = null): ?VendorPayout
    {
        return DB::transaction(function () use ($vendor, $periodStart, $periodEnd): ?VendorPayout {
            $query = OrderItem::query()
                ->where('vendor_id', $vendor->id)
                ->where('vendor_status', VendorSubOrderStatus::Delivered)
                ->whereNull('payout_id');

            if ($periodStart !== null) {
                $query->where('created_at', '>=', $periodStart);
            }

            if ($periodEnd !== null) {
                $query->where('created_at', '<=', $periodEnd);
            }

            $items = $query->lockForUpdate()->get(['id', 'vendor_earning', 'created_at']);

            if ($items->isEmpty()) {
                return null;
            }

            $totalEarnings = $items->sum('vendor_earning');

            $payout = VendorPayout::query()->create([
                'vendor_id' => $vendor->id,
                'period_start' => $periodStart ?? $items->min('created_at'),
                'period_end' => $periodEnd ?? $items->max('created_at'),
                'total_earnings' => $totalEarnings,
                'status' => PayoutStatus::Pending,
            ]);

            OrderItem::query()
                ->whereIn('id', $items->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            return $payout;
        });
    }
}
