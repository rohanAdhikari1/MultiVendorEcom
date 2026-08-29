<?php

namespace App\Models;

use App\Enums\BorderRadiusScale;
use App\Enums\ButtonStyle;
use App\Enums\LayoutDensity;
use App\Enums\ShadowStyle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $site_name
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property string $primary_color
 * @property string $secondary_color
 * @property string $accent_color
 * @property string $font_family
 * @property BorderRadiusScale $border_radius_scale
 * @property ShadowStyle $shadow_style
 * @property LayoutDensity $layout_density
 * @property ButtonStyle $button_style
 * @property string $default_commission_percent
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property array<string, mixed>|null $social_links
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'site_name',
    'logo_path',
    'favicon_path',
    'primary_color',
    'secondary_color',
    'accent_color',
    'font_family',
    'border_radius_scale',
    'shadow_style',
    'layout_density',
    'button_style',
    'default_commission_percent',
    'contact_email',
    'contact_phone',
    'social_links',
])]
class SiteSetting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_commission_percent' => 'decimal:2',
            'social_links' => 'array',
            'border_radius_scale' => BorderRadiusScale::class,
            'shadow_style' => ShadowStyle::class,
            'layout_density' => LayoutDensity::class,
            'button_style' => ButtonStyle::class,
        ];
    }

    /**
     * Get the single settings row, creating it with defaults if it
     * does not exist yet.
     *
     * `firstOrCreate()` only sets `id` on the in-memory instance it
     * inserts — every other column's DB-level default (site_name,
     * primary_color, etc.) is never reflected back into that object, so a
     * just-created row is re-fetched to pick up the real stored defaults.
     */
    public static function current(): self
    {
        $settings = static::query()->firstOrCreate(['id' => 1]);

        return $settings->wasRecentlyCreated ? $settings->fresh() ?? $settings : $settings;
    }
}
