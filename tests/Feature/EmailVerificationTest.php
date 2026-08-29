<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registering fires the email verification notification', function () {
    Notification::fake();

    Livewire::test(Register::class)
        ->set('name', 'Verify Me')
        ->set('email', 'verify-me@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertRedirect(route('home'));

    $user = User::query()->where('email', 'verify-me@example.com')->firstOrFail();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('visiting a valid signed verification link marks the email as verified and redirects home', function () {
    $user = User::factory()->unverified()->create();

    expect($user->hasVerifiedEmail())->toBeFalse();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(route('home'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('an unverified user can still browse the homepage and sees the verification nudge', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertSee('Please verify your email address');
    $response->assertSee('Resend verification email');
});

test('a verified user does not see the verification nudge', function () {
    $user = User::factory()->create(); // verified by default via the factory

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Please verify your email address');
});

test('the resend verification link route sends a new notification and is rate limited', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyEmail::class);

    // A second immediate request should be throttled rather than sending
    // unlimited verification emails.
    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertStatus(429);
});
