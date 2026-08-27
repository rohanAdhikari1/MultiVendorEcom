<?php

namespace App\Services\Theme;

use App\Enums\BorderRadiusScale;
use App\Enums\ButtonStyle;
use App\Enums\LayoutDensity;
use App\Enums\ShadowStyle;
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

    private const DEFAULT_BORDER_RADIUS_SCALE = BorderRadiusScale::Rounded;

    private const DEFAULT_SHADOW_STYLE = ShadowStyle::Soft;

    private const DEFAULT_LAYOUT_DENSITY = LayoutDensity::Comfortable;

    private const DEFAULT_BUTTON_STYLE = ButtonStyle::Solid;

    private const CACHE_KEY = 'site-settings';

    /**
     * Get the cached singleton site settings row.
     *
     * Caches the model's raw attributes (a plain array) rather than the
     * Eloquent model instance itself: persistent cache stores (database,
     * file, redis, ...) serialize values with PHP's unserialize(), and
     * PHP/Laravel restrict which classes are safe to reconstruct that way.
     * An Eloquent model round-tripped through such a store comes back as
     * an unusable `__PHP_Incomplete_Class` instead of a `SiteSetting`.
     * Plain arrays/scalars have no such restriction, so we cache those and
     * rehydrate a real model instance on every read instead.
     */
    public function settings(): SiteSetting
    {
        /** @var array<string, mixed> $attributes */
        $attributes = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(5),
            fn () => SiteSetting::current()->getAttributes(),
        );

        return (new SiteSetting)->newFromBuilder($attributes);
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
            .$this->radiusVariables($this->resolveBorderRadiusScale($settings))
            .$this->shadowVariables($this->resolveShadowStyle($settings))
            .$this->densityVariables($this->resolveLayoutDensity($settings))
            .'}';
    }

    /**
     * The current border-radius scale, falling back to the default whenever
     * the stored value is blank or doesn't match a known case (e.g. a
     * legacy row from before this column existed, or a malformed manual
     * edit) — accessed via the raw attribute rather than the enum-cast
     * accessor so a bad stored value can never throw and break the page.
     */
    public function borderRadiusScale(): BorderRadiusScale
    {
        return $this->resolveBorderRadiusScale($this->settings());
    }

    /**
     * The current shadow depth style, with the same defensive fallback as
     * {@see borderRadiusScale()}.
     */
    public function shadowStyle(): ShadowStyle
    {
        return $this->resolveShadowStyle($this->settings());
    }

    /**
     * The current layout spacing density, with the same defensive fallback
     * as {@see borderRadiusScale()}.
     */
    public function layoutDensity(): LayoutDensity
    {
        return $this->resolveLayoutDensity($this->settings());
    }

    /**
     * The current default treatment for `x-ui.button`'s primary variant,
     * with the same defensive fallback as {@see borderRadiusScale()}.
     * Consumed directly by the `x-ui.button` component to resolve which
     * variant classes to render when no explicit `variant` prop is given.
     */
    public function buttonStyle(): ButtonStyle
    {
        return ButtonStyle::tryFrom((string) $this->settings()->getRawOriginal('button_style'))
            ?? self::DEFAULT_BUTTON_STYLE;
    }

    private function resolveBorderRadiusScale(SiteSetting $settings): BorderRadiusScale
    {
        return BorderRadiusScale::tryFrom((string) $settings->getRawOriginal('border_radius_scale'))
            ?? self::DEFAULT_BORDER_RADIUS_SCALE;
    }

    private function resolveShadowStyle(SiteSetting $settings): ShadowStyle
    {
        return ShadowStyle::tryFrom((string) $settings->getRawOriginal('shadow_style'))
            ?? self::DEFAULT_SHADOW_STYLE;
    }

    private function resolveLayoutDensity(SiteSetting $settings): LayoutDensity
    {
        return LayoutDensity::tryFrom((string) $settings->getRawOriginal('layout_density'))
            ?? self::DEFAULT_LAYOUT_DENSITY;
    }

    /**
     * Translate the border-radius scale into concrete `--radius-*` tokens.
     * These names match Tailwind v4's `rounded-{sm,md,lg}` utility-generation
     * convention (see the `@theme` block in resources/css/app.css, which
     * defines the build-time defaults these override at runtime).
     */
    private function radiusVariables(BorderRadiusScale $scale): string
    {
        [$sm, $md, $lg] = match ($scale) {
            BorderRadiusScale::None => ['0px', '0px', '0px'],
            BorderRadiusScale::Subtle => ['0.125rem', '0.25rem', '0.375rem'],
            BorderRadiusScale::Rounded => ['0.25rem', '0.375rem', '0.5rem'],
            BorderRadiusScale::Pill => ['9999px', '9999px', '9999px'],
        };

        return '--radius-sm:'.$sm.';--radius-md:'.$md.';--radius-lg:'.$lg.';';
    }

    /**
     * Translate the shadow style into concrete `--shadow-*` tokens, matching
     * Tailwind v4's `shadow-{sm,lg}` utility-generation convention.
     */
    private function shadowVariables(ShadowStyle $style): string
    {
        [$sm, $lg] = match ($style) {
            ShadowStyle::Flat => ['none', 'none'],
            ShadowStyle::Soft => [
                '0 1px 2px 0 rgb(0 0 0 / 0.05)',
                '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
            ],
            ShadowStyle::Elevated => [
                '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
                '0 20px 25px -5px rgb(0 0 0 / 0.15), 0 8px 10px -6px rgb(0 0 0 / 0.15)',
            ],
        };

        return '--shadow-sm:'.$sm.';--shadow-lg:'.$lg.';';
    }

    /**
     * Translate the layout density into `--card-padding`/`--section-spacing`
     * custom properties. Tailwind v4 utility classes are static at build
     * time, so density is applied at a handful of well-chosen wrapper
     * points (product cards, content-block sections) via `p-(--card-padding)`
     * / `py-(--section-spacing)`-style utilities that read these tokens
     * directly, rather than every spacing utility in the app being dynamic.
     */
    private function densityVariables(LayoutDensity $density): string
    {
        [$cardPadding, $sectionSpacing] = match ($density) {
            LayoutDensity::Compact => ['0.5rem', '1.5rem'],
            LayoutDensity::Comfortable => ['0.75rem', '2.5rem'],
            LayoutDensity::Spacious => ['1.25rem', '4rem'],
        };

        return '--card-padding:'.$cardPadding.';--section-spacing:'.$sectionSpacing.';';
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
