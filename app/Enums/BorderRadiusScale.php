<?php

namespace App\Enums;

/**
 * Sitewide corner-rounding scale for cards, buttons, and inputs, admin
 * -configurable from the Filament "Site Settings" page. Translated into
 * concrete `--radius-*` CSS custom properties by ThemeSettingsService.
 */
enum BorderRadiusScale: string
{
    case None = 'none';
    case Subtle = 'subtle';
    case Rounded = 'rounded';
    case Pill = 'pill';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None (square corners)',
            self::Subtle => 'Subtle',
            self::Rounded => 'Rounded',
            self::Pill => 'Pill (fully rounded)',
        };
    }
}
