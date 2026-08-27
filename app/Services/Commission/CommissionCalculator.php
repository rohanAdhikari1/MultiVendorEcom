<?php

namespace App\Services\Commission;

use App\Models\SiteSetting;
use App\Models\VendorProfile;

/**
 * Resolves and applies the platform's commission split for a single order
 * line: the vendor's own override percent if they have one, otherwise the
 * platform-wide default from SiteSetting. There is no category-level tier
 * in this data model — commission is a vendor-or-platform-default lookup
 * only.
 */
class CommissionCalculator
{
    /**
     * @return array{percent: float, commission: float, vendorEarning: float}
     */
    public function calculate(VendorProfile $vendor, float $lineTotal): array
    {
        $percent = $vendor->commission_override_percent !== null
            ? (float) $vendor->commission_override_percent
            : (float) SiteSetting::current()->default_commission_percent;

        $commission = round($lineTotal * $percent / 100, 2);
        $vendorEarning = round($lineTotal - $commission, 2);

        return [
            'percent' => $percent,
            'commission' => $commission,
            'vendorEarning' => $vendorEarning,
        ];
    }
}
