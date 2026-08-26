<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\VendorApplicationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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

        foreach ($productNames as $index => $name) {
            Product::factory()->create([
                'vendor_id' => $vendorProfile->id,
                'category_id' => $categories->get($index % $categories->count())->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.($index + 1),
                'status' => ProductStatus::Published,
                'is_featured' => in_array($index, [0, 3], true),
            ]);
        }
    }
}
