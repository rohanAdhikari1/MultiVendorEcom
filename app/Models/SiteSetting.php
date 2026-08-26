<?php

namespace App\Models;

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
        ];
    }

    /**
     * Get the single settings row, creating it with defaults if it
     * does not exist yet.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
