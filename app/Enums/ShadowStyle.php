<?php

namespace App\Enums;

/**
 * Sitewide shadow depth for cards and buttons, admin-configurable from the
 * Filament "Site Settings" page. Translated into concrete `--shadow-*` CSS
 * custom properties by ThemeSettingsService.
 */
enum ShadowStyle: string
{
    case Flat = 'flat';
    case Soft = 'soft';
    case Elevated = 'elevated';

    public function label(): string
    {
        return match ($this) {
            self::Flat => 'Flat (no shadow)',
            self::Soft => 'Soft',
            self::Elevated => 'Elevated',
        };
    }
}
