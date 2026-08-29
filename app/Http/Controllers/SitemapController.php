<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\VendorProfile;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Generates a minimal, standard XML sitemap covering the storefront's
 * highest-value indexable URLs: the homepage, the catalog, every Published
 * product, every Approved vendor's shop, and each active category's
 * catalog-filter URL. Deliberately excludes anything not publicly
 * searchable (draft/archived products, pending/rejected/suspended vendors)
 * and every authenticated area (cart, checkout, orders, vendor dashboard,
 * admin) -- see public/robots.txt for that boundary.
 *
 * Three bounded, single-table queries -- not a query per URL -- keep this
 * cheap regardless of catalog size.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        /** @var array<int, array{loc: string, lastmod: ?Carbon}> $urls */
        $urls = [
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('products.index'), 'lastmod' => null],
        ];

        $products = Product::query()
            ->where('status', ProductStatus::Published)
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->get();

        foreach ($products as $product) {
            $urls[] = [
                'loc' => route('products.show', $product->slug),
                'lastmod' => $product->updated_at,
            ];
        }

        $vendors = VendorProfile::query()
            ->where('status', VendorApplicationStatus::Approved)
            ->select(['shop_slug', 'updated_at'])
            ->orderBy('id')
            ->get();

        foreach ($vendors as $vendor) {
            $urls[] = [
                'loc' => route('store.show', $vendor->shop_slug),
                'lastmod' => $vendor->updated_at,
            ];
        }

        $categories = Category::query()
            ->where('is_active', true)
            ->select(['slug', 'updated_at'])
            ->orderBy('sort_order')
            ->get();

        foreach ($categories as $category) {
            $urls[] = [
                'loc' => route('products.index', ['category' => $category->slug]),
                'lastmod' => $category->updated_at,
            ];
        }

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
