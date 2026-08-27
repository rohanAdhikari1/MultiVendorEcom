<?php

namespace App\Enums;

/**
 * Sitewide spacing density for card padding and section spacing,
 * admin-configurable from the Filament "Site Settings" page. Translated
 * into concrete `--card-padding`/`--section-spacing` CSS custom properties
 * by ThemeSettingsService.
 */
enum LayoutDensity: string
{
    case Compact = 'compact';
    case Comfortable = 'comfortable';
    case Spacious = 'spacious';

    public function label(): string
    {
        return match ($this) {
            self::Compact => 'Compact',
            self::Comfortable => 'Comfortable',
            self::Spacious => 'Spacious',
        };
    }
}
