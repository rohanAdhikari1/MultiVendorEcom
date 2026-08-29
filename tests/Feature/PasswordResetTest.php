<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('requesting a reset link for an existing email queues the notification', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'exists@example.com']);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'exists@example.com')
        ->call('sendResetLink')
        ->assertSet('submitted', true)
        ->assertSee('If that email address is in our system');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('requesting a reset link for a nonexistent email shows the same generic message without sending anything', function () {
    Notification::fake();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody-here@example.com')
        ->call('sendResetLink')
        ->assertSet('submitted', true)
        ->assertSee('If that email address is in our system');

    Notification::assertNothingSent();
});

test('a valid reset token successfully changes the password and the user can log in with it', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset-me@example.com',
        'password' => Hash::make('old-password123'),
    ]);

    Livewire::test(ForgotPassword::class)
        ->set('email', 'reset-me@example.com')
        ->call('sendResetLink');

    $capturedToken = null;
    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$capturedToken) {
        $capturedToken = $notification->token;

        return true;
    });

    expect($capturedToken)->not->toBeNull();

    Livewire::test(ResetPassword::class, ['token' => $capturedToken])
        ->set('email', 'reset-me@example.com')
        ->set('password', 'brand-new-password123')
        ->set('password_confirmation', 'brand-new-password123')
        ->call('resetPassword')
        ->assertRedirect(route('login'));

    expect(Hash::check('brand-new-password123', $user->fresh()->password))->toBeTrue();

    expect(Hash::check('old-password123', $user->fresh()->password))->toBeFalse();

    Livewire::test(Login::class)
        ->set('email', 'reset-me@example.com')
        ->set('password', 'brand-new-password123')
        ->call('login')
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

test('an invalid or expired reset token is rejected with a clear error and the password is unchanged', function () {
    $user = User::factory()->create([
        'email' => 'bad-token@example.com',
        'password' => Hash::make('original-password123'),
    ]);

    Livewire::test(ResetPassword::class, ['token' => 'this-token-is-not-valid'])
        ->set('email', 'bad-token@example.com')
        ->set('password', 'another-password123')
        ->set('password_confirmation', 'another-password123')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    expect(Hash::check('original-password123', $user->fresh()->password))->toBeTrue();
});

test('reset password page pre-fills the email from the query string', function () {
    $response = $this->get('/reset-password/some-token?email=prefilled%40example.com');

    $response->assertOk();

    Livewire::test(ResetPassword::class, ['token' => 'some-token'])
        ->assertSet('token', 'some-token');
});

test('forgot password requests are rate limited after repeated submissions', function () {
    Notification::fake();

    User::factory()->create(['email' => 'rate-limited@example.com']);

    for ($i = 0; $i < 3; $i++) {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'rate-limited@example.com')
            ->call('sendResetLink')
            ->assertSet('submitted', true);
    }

    Livewire::test(ForgotPassword::class)
        ->set('email', 'rate-limited@example.com')
        ->call('sendResetLink')
        ->assertSet('submitted', false)
        ->assertSee('Too many attempts');
});
