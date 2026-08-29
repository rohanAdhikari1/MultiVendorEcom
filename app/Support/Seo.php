<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Small text-shaping helper for building `<meta name="description">` /
 * `og:description` content out of user-authored fields (product and vendor
 * descriptions) that may contain HTML.
 */
class Seo
{
    /**
     * Strip any HTML tags and collapse whitespace from $text, then truncate
     * to the SEO-conventional meta-description length (~155-160 chars by
     * default). Tags are stripped *before* truncating so the cut can never
     * land mid-tag or leak a broken attribute into the rendered
     * `content="..."` value. Returns null for blank/empty input so callers
     * can layer their own fallback with `??`.
     */
    public static function excerpt(?string $text, int $limit = 160): ?string
    {
        $plain = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $text)));

        if ($plain === '') {
            return null;
        }

        return Str::limit($plain, $limit, '...');
    }
}
