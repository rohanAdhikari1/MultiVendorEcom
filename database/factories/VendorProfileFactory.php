<?php

namespace Database\Factories;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorProfile>
 */
class VendorProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shopName = fake()->unique()->company();

        return [
            'user_id' => User::factory(),
            'shop_name' => $shopName,
            'shop_slug' => Str::slug($shopName).'-'.fake()->unique()->numberBetween(1000, 9999),
            'logo_path' => null,
            'banner_path' => null,
            'description' => fake()->paragraph(),
            'status' => VendorApplicationStatus::Pending,
            'commission_override_percent' => null,
            'payout_details' => null,
            'approved_at' => null,
            'rejected_reason' => null,
        ];
    }

    /**
     * Indicate that the vendor application is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VendorApplicationStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
