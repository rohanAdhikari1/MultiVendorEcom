<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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

    public function login(): void
    {
        $credentials = $this->validate();

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $this->remember)) {
            $this->addError('email', __('These credentials do not match our records.'));

            return;
        }

        session()->regenerate();

        $this->redirect(route('home'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
