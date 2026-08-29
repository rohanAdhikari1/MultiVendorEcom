<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Maximum failed login attempts (per email + IP) before lockout.
     */
    protected int $maxAttempts = 5;

    public function login(): void
    {
        $credentials = $this->validate();

        if ($this->tooManyAttempts()) {
            return;
        }

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            $this->addError('email', __('These credentials do not match our records.'));

            return;
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        $this->redirect(route('home'), navigate: false);
    }

    /**
     * Check the login rate limiter, adding a validation error and returning
     * true when this email + IP combination has been locked out.
     */
    protected function tooManyAttempts(): bool
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts)) {
            return false;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->addError('email', __('Too many login attempts. Please try again in :seconds seconds.', [
            'seconds' => $seconds,
        ]));

        return true;
    }

    /**
     * Rate limiter key, scoped to the submitted email and the requesting IP
     * so a single attacker can't lock out someone else's account by
     * hammering their email from a different address, nor churn through
     * many emails from one IP without ever tripping the limiter.
     */
    protected function throttleKey(): string
    {
        return Str::lower($this->email).'|'.request()->ip();
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
