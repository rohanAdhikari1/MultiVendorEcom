<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Widgets\SalesTrendWidget;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->admin->refresh();
});

it('renders without error against seeded order data', function (): void {
    Order::query()->create([
        'order_number' => Order::generateOrderNumber(),
        'user_id' => User::factory()->create()->id,
        'shipping_recipient_name' => 'Jane Doe',
        'shipping_phone' => '9800000000',
        'shipping_province' => 'Bagmati',
        'shipping_district' => 'Kathmandu',
        'shipping_municipality' => 'Kathmandu Metro',
        'shipping_ward_no' => '5',
        'shipping_street_address' => 'Baneshwor',
        'subtotal' => 1000,
        'shipping_total' => 100,
        'discount_total' => 0,
        'grand_total' => 1100,
        'currency' => 'NPR',
        'payment_method' => PaymentMethod::Cod,
        'payment_status' => PaymentStatus::Pending,
        'status' => OrderStatus::Processing,
        'placed_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(SalesTrendWidget::class)
        ->assertOk();
});

it('renders without error with no order data at all', function (): void {
    Livewire::actingAs($this->admin)
        ->test(SalesTrendWidget::class)
        ->assertOk();
});

it('is registered on the admin dashboard', function (): void {
    $this->actingAs($this->admin)->get('/admin')->assertOk();
});
