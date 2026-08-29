<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MarketplaceDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendorUser = User::factory()->create([
            'name' => 'Demo Vendor',
            'email' => 'vendor@example.com',
        ]);
        $vendorUser->assignRole('vendor');

        $vendorProfile = VendorProfile::query()->create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Himalayan Bazaar',
            'shop_slug' => 'himalayan-bazaar',
            'description' => 'A curated demo storefront selling electronics, fashion, and home essentials.',
            'status' => VendorApplicationStatus::Approved,
            'approved_at' => now(),
        ]);

        $categories = collect([
            'Electronics',
            'Fashion',
            'Home & Kitchen',
            'Books',
            'Groceries',
        ])->map(fn (string $name) => Category::query()->create([
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => 0,
        ]));

        $productNames = [
            'Wireless Bluetooth Earbuds',
            'Smart LED Desk Lamp',
            'Men\'s Casual Cotton Shirt',
            'Women\'s Denim Jacket',
            'Non-Stick Cookware Set',
            'Stainless Steel Water Bottle',
            'Bestselling Fiction Novel Collection',
            'Organic Basmati Rice 5kg',
            'Portable Power Bank 20000mAh',
            'Handwoven Wool Scarf',
        ];

        $products = collect();

        foreach ($productNames as $index => $name) {
            $products->push(Product::factory()->create([
                'vendor_id' => $vendorProfile->id,
                'category_id' => $categories->get($index % $categories->count())->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.($index + 1),
                'status' => ProductStatus::Published,
                'is_featured' => in_array($index, [0, 3], true),
            ]));
        }

        $this->seedDemoReviews($products);
    }

    /**
     * A handful of demo reviews (one with a vendor reply already attached)
     * so the moderation + reply feature is visibly demonstrable out of the
     * box rather than requiring manual data entry.
     *
     * @param  Collection<int, Product>  $products
     */
    private function seedDemoReviews($products): void
    {
        $reviewer = User::factory()->create([
            'name' => 'Demo Customer',
            'email' => 'customer@example.com',
        ]);
        $reviewer->assignRole('customer');

        Review::query()->create([
            'product_id' => $products->get(0)->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'body' => 'Excellent sound quality and the battery lasts all day. Highly recommend!',
            'is_visible' => true,
            'vendor_reply' => 'Thank you so much for the kind words — glad you\'re enjoying them!',
            'vendor_replied_at' => now(),
        ]);

        Review::query()->create([
            'product_id' => $products->get(0)->id,
            'user_id' => $reviewer->id,
            'rating' => 3,
            'body' => 'Good product overall, but the packaging could be sturdier.',
            'is_visible' => true,
        ]);

        Review::query()->create([
            'product_id' => $products->get(1)->id,
            'user_id' => $reviewer->id,
            'rating' => 4,
            'body' => 'Bright and adjustable, exactly what I needed for my desk.',
            'is_visible' => true,
        ]);
    }
}
