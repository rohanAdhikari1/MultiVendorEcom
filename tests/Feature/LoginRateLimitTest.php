<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('login is blocked with a clear message after too many failed attempts from the same email and IP', function () {
    $user = User::factory()->create([
        'email' => 'throttle-me@example.com',
        'password' => Hash::make('correct-password123'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'throttle-me@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSee('These credentials do not match our records.');
    }

    // The 6th attempt is blocked by the rate limiter — even with the
    // correct password — rather than allowed through or a raw 500.
    Livewire::test(Login::class)
        ->set('email', 'throttle-me@example.com')
        ->set('password', 'correct-password123')
        ->call('login')
        ->assertHasErrors(['email'])
        ->assertSee('Too many login attempts');

    $this->assertGuest();
});

test('rate limiting is scoped per email so other accounts can still log in', function () {
    $lockedOutUser = User::factory()->create([
        'email' => 'locked-out@example.com',
        'password' => Hash::make('password123'),
    ]);

    $otherUser = User::factory()->create([
        'email' => 'unaffected@example.com',
        'password' => Hash::make('password123'),
    ]);

    for ($i = 0; $i < 6; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'locked-out@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(Login::class)
        ->set('email', 'unaffected@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($otherUser);
});

test('a successful login clears the rate limiter for that email', function () {
    $user = User::factory()->create([
        'email' => 'clears-limiter@example.com',
        'password' => Hash::make('password123'),
    ]);

    for ($i = 0; $i < 3; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'clears-limiter@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test(Login::class)
        ->set('email', 'clears-limiter@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});
