<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $order_number
 * @property int $user_id
 * @property string $shipping_recipient_name
 * @property string $shipping_phone
 * @property string $shipping_province
 * @property string $shipping_district
 * @property string $shipping_municipality
 * @property string|null $shipping_ward_no
 * @property string|null $shipping_street_address
 * @property string $subtotal
 * @property string $shipping_total
 * @property string $discount_total
 * @property string $grand_total
 * @property string $currency
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $payment_status
 * @property OrderStatus $status
 * @property Carbon|null $placed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, PaymentTransaction> $paymentTransactions
 */
#[Fillable([
    'order_number',
    'user_id',
    'shipping_recipient_name',
    'shipping_phone',
    'shipping_province',
    'shipping_district',
    'shipping_municipality',
    'shipping_ward_no',
    'shipping_street_address',
    'subtotal',
    'shipping_total',
    'discount_total',
    'grand_total',
    'currency',
    'payment_method',
    'payment_status',
    'status',
    'placed_at',
])]
class Order extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'status' => OrderStatus::class,
            'placed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<PaymentTransaction, $this>
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Generate a unique, human-friendly order number in the form
     * ORD-YYYYMMDD-XXXX (a random 4 character alphanumeric suffix,
     * re-rolled on the rare occasion of a collision).
     */
    public static function generateOrderNumber(): string
    {
        do {
            $candidate = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(4));
        } while (static::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
