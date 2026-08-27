<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'CODE'.fake()->unique()->numberBetween(1000, 9999),
            'type' => CouponType::Percent,
            'value' => 10,
            'min_order_amount' => null,
            'max_uses' => null,
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function percent(float $value = 10): self
    {
        return $this->state(fn (): array => [
            'type' => CouponType::Percent,
            'value' => $value,
        ]);
    }

    public function fixed(float $value = 100): self
    {
        return $this->state(fn (): array => [
            'type' => CouponType::Fixed,
            'value' => $value,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function notYetStarted(): self
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->addDay(),
        ]);
    }

    public function maxUsesReached(int $maxUses = 1): self
    {
        return $this->state(fn (): array => [
            'max_uses' => $maxUses,
            'used_count' => $maxUses,
        ]);
    }
}
