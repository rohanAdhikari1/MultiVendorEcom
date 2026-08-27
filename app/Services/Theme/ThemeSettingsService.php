<?php

namespace App\Services\Theme;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Wraps `SiteSetting::current()` behind a short-TTL cache and exposes the
 * generated CSS custom properties used to theme the storefront at runtime.
 *
 * The 5-minute TTL is a deliberate, simple choice: admin theme edits
 * propagate automatically within a few minutes without any cross-service
 * cache-invalidation coordination against `SiteSetting` itself.
 */
class ThemeSettingsService
{
    /**
     * Fallback values, matching the `site_settings` migration's column
     * defaults, used whenever a stored value is missing or malformed so
     * the generated stylesheet can never break the page.
     */
    private const DEFAULT_PRIMARY_COLOR = '#4f46e5';

    private const DEFAULT_SECONDARY_COLOR = '#0ea5e9';

    private const DEFAULT_ACCENT_COLOR = '#f59e0b';

    private const DEFAULT_FONT_FAMILY = 'Instrument Sans';

    private const CACHE_KEY = 'site-settings';

    /**
     * Get the cached singleton site settings row.
     */
    public function settings(): SiteSetting
    {
        /** @var SiteSetting */
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(5), fn () => SiteSetting::current());
    }

    /**
     * Build a ready-to-embed `:root{...}` CSS custom-property block
     * reflecting the current site settings. Falls back to sensible
     * defaults for any blank/invalid color or font value.
     */
    public function cssVariables(): string
    {
        $settings = $this->settings();

        $primary = $this->sanitizeColor($settings->primary_color, self::DEFAULT_PRIMARY_COLOR);
        $secondary = $this->sanitizeColor($settings->secondary_color, self::DEFAULT_SECONDARY_COLOR);
        $accent = $this->sanitizeColor($settings->accent_color, self::DEFAULT_ACCENT_COLOR);
        $fontStack = $this->fontStack($settings->font_family);

        return ':root{'
            .'--color-primary:'.$primary.';'
            .'--color-secondary:'.$secondary.';'
            .'--color-accent:'.$accent.';'
            .'--font-sans:'.$fontStack.';'
            .'}';
    }

    /**
     * Ensure the value looks like a usable hex CSS color, otherwise fall
     * back to the given default.
     */
    private function sanitizeColor(?string $value, string $default): string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) !== 1) {
            return $default;
        }

        return $value;
    }

    /**
     * Build a properly quoted CSS font-family stack from the configured
     * font name, falling back to the default font when blank.
     */
    private function fontStack(?string $fontFamily): string
    {
        $fontFamily = trim((string) $fontFamily);

        if ($fontFamily === '') {
            $fontFamily = self::DEFAULT_FONT_FAMILY;
        }

        $quoted = "'".str_replace("'", "\\'", $fontFamily)."'";

        return $quoted.',ui-sans-serif,system-ui,sans-serif';
    }
}
