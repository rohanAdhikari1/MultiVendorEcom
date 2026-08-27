<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a guest can register, is assigned the customer role, and is logged in', function () {
    Livewire::test(Register::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect(route('home'));

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect($user->hasRole('customer'))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('registration requires matching password confirmation', function () {
    Livewire::test(Register::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'not-the-same')
        ->call('register')
        ->assertHasErrors(['password']);

    $this->assertGuest();
});

test('a user can log in with correct credentials and log out', function () {
    $user = User::factory()->create([
        'email' => 'login-test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $user->assignRole('customer');

    Livewire::test(Login::class)
        ->set('email', 'login-test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('login fails with incorrect credentials', function () {
    $user = User::factory()->create([
        'email' => 'wrong-pass@example.com',
        'password' => Hash::make('password123'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'wrong-pass@example.com')
        ->set('password', 'incorrect-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('guests are redirected away from login and register pages once authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('login'))->assertRedirect(route('home'));
    $this->actingAs($user)->get(route('register'))->assertRedirect(route('home'));
});

test('guests can view login and register pages', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

test('logout requires authentication', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});
