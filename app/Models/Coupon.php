<?php

namespace App\Models;

use App\Enums\CouponType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code
 * @property CouponType $type
 * @property string $value
 * @property string|null $min_order_amount
 * @property int|null $max_uses
 * @property int $used_count
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'code',
    'type',
    'value',
    'min_order_amount',
    'max_uses',
    'used_count',
    'starts_at',
    'expires_at',
    'is_active',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Coupon codes are always stored (and therefore compared) in uppercase,
     * so lookups can do a plain equality match on Str::upper($input) rather
     * than a case-insensitive SQL comparison.
     *
     * @return Attribute<string, string>
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::upper(trim($value)),
        );
    }

    /**
     * Whether this coupon can currently be applied to a cart with the given
     * subtotal: active, within its start/expiry window, under its max-use
     * cap, and the subtotal meets its minimum order amount (if any).
     */
    public function isValidFor(float $subtotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at !== null && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * The discount this coupon grants for a given subtotal. Never exceeds
     * the subtotal itself, so a large fixed-amount coupon on a small cart
     * caps out at the subtotal rather than producing a negative total.
     */
    public function discountFor(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $discount = match ($this->type) {
            CouponType::Percent => $subtotal * ((float) $this->value / 100),
            CouponType::Fixed => (float) $this->value,
        };

        return round(min($discount, $subtotal), 2);
    }
}
