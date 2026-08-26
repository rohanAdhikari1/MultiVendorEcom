<?php

namespace App\Models;

use App\Enums\VendorApplicationStatus;
use Database\Factories\VendorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $shop_name
 * @property string $shop_slug
 * @property string|null $logo_path
 * @property string|null $banner_path
 * @property string|null $description
 * @property VendorApplicationStatus $status
 * @property string|null $commission_override_percent
 * @property array<string, mixed>|null $payout_details
 * @property Carbon|null $approved_at
 * @property string|null $rejected_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Product> $products
 */
#[Fillable([
    'user_id',
    'shop_name',
    'shop_slug',
    'logo_path',
    'banner_path',
    'description',
    'status',
    'commission_override_percent',
    'payout_details',
    'approved_at',
    'rejected_reason',
])]
class VendorProfile extends Model
{
    /** @use HasFactory<VendorProfileFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VendorApplicationStatus::class,
            'commission_override_percent' => 'decimal:2',
            'payout_details' => 'array',
            'approved_at' => 'datetime',
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
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }
}
