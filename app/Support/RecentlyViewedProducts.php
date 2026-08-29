<?php

namespace App\Support;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Session-based "recently viewed products" tracking shared between
 * ProductDetail (records a view + shows the list minus the current product)
 * and Home (shows the list for a returning visitor).
 *
 * Session-only by design for both guests and logged-in users: this is a
 * lightweight browsing convenience, not order/account history, so there is
 * no cross-device continuity requirement that would justify a DB table and
 * a migration for Phase 1. See WishlistItem for the DB-backed pattern this
 * project already uses when persistence genuinely matters.
 */
class RecentlyViewedProducts
{
    private const SESSION_KEY = 'recently_viewed_products';

    private const LIMIT = 12;

    /**
     * Record a view of $productId, most-recent-first. Re-viewing a product
     * already in the list moves it back to the front instead of creating a
     * duplicate entry. The list is capped at self::LIMIT.
     */
    public static function remember(int $productId): void
    {
        $ids = self::rawIds()
            ->reject(fn (int $id): bool => $id === $productId)
            ->prepend($productId)
            ->take(self::LIMIT)
            ->values()
            ->all();

        session()->put(self::SESSION_KEY, $ids);
    }

    /**
     * Fetch the viewed products in most-recent-first order, eager-loaded to
     * match the shape ProductCatalog/VendorShopPage already establish for
     * <x-ui.product-card> (vendor, primaryImage, reviews avg/count) so this
     * never becomes an N+1 as more cards render.
     *
     * @return Collection<int, Product>
     */
    public static function forDisplay(?int $excludeProductId = null, int $limit = self::LIMIT): Collection
    {
        $ids = self::rawIds()
            ->when($excludeProductId !== null, fn (Collection $c) => $c->reject(fn (int $id): bool => $id === $excludeProductId))
            ->take($limit)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with(['vendor', 'primaryImage'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('status', ProductStatus::Published)
            ->whereIn('id', $ids)
            ->get();

        // Re-order to match the session list's most-recent-first order --
        // whereIn does not preserve it, and a product that was unpublished
        // or deleted since being viewed is simply dropped.
        return $ids
            ->map(fn (int $id) => $products->firstWhere('id', $id))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, int>
     */
    private static function rawIds(): Collection
    {
        /** @var array<int, mixed> $stored */
        $stored = session()->get(self::SESSION_KEY, []);

        return collect($stored)->map(fn ($id): int => (int) $id);
    }
}
