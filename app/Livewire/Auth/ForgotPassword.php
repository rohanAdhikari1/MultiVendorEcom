<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ForgotPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    /**
     * Set once a reset link request has gone through successfully, so the
     * view can swap the form for a generic confirmation message.
     */
    public bool $submitted = false;

    protected int $maxAttempts = 3;

    public function sendResetLink(): void
    {
        $this->validate();

        if ($this->tooManyAttempts()) {
            return;
        }

        RateLimiter::hit($this->throttleKey());

        // Intentionally ignore the returned status. Whether the email
        // exists or not, the user sees the same generic confirmation —
        // revealing the difference would let an attacker enumerate
        // registered accounts.
        Password::sendResetLink(['email' => $this->email]);

        $this->submitted = true;
    }

    protected function tooManyAttempts(): bool
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts)) {
            return false;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->addError('email', __('Too many attempts. Please try again in :seconds seconds.', [
            'seconds' => $seconds,
        ]));

        return true;
    }

    protected function throttleKey(): string
    {
        return 'forgot-password|'.Str::lower($this->email).'|'.request()->ip();
    }

    public function render(): View
    {
        return view('livewire.auth.forgot-password');
    }
}
