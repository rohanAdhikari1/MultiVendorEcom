<?php

namespace App\Models;

use App\Enums\VendorSubOrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property int $vendor_id
 * @property string $product_name_snapshot
 * @property string $unit_price_snapshot
 * @property int $quantity
 * @property string $line_total
 * @property string $commission_percent_snapshot
 * @property string $commission_amount
 * @property string $vendor_earning
 * @property VendorSubOrderStatus $vendor_status
 * @property int|null $payout_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 * @property-read Product|null $product
 * @property-read ProductVariant|null $productVariant
 * @property-read VendorProfile $vendor
 * @property-read VendorPayout|null $payout
 */
#[Fillable([
    'order_id',
    'product_id',
    'product_variant_id',
    'vendor_id',
    'product_name_snapshot',
    'unit_price_snapshot',
    'quantity',
    'line_total',
    'commission_percent_snapshot',
    'commission_amount',
    'vendor_earning',
    'vendor_status',
    'payout_id',
])]
class OrderItem extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_snapshot' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
            'commission_percent_snapshot' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'vendor_earning' => 'decimal:2',
            'vendor_status' => VendorSubOrderStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @return BelongsTo<VendorProfile, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<VendorPayout, $this>
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class, 'payout_id');
    }
}
