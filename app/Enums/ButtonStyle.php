<?php

namespace App\Enums;

/**
 * Default visual treatment of `x-ui.button`'s primary variant,
 * admin-configurable from the Filament "Site Settings" page. Read by the
 * `x-ui.button` component (via ThemeSettingsService) to resolve which
 * variant classes to render when no explicit `variant` prop is given.
 */
enum ButtonStyle: string
{
    case Solid = 'solid';
    case Outline = 'outline';
    case Soft = 'soft';

    public function label(): string
    {
        return match ($this) {
            self::Solid => 'Solid',
            self::Outline => 'Outline',
            self::Soft => 'Soft',
        };
    }
}
