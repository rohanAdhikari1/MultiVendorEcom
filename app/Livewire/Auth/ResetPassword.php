<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class ResetPassword extends Component
{
    public string $token = '';

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Bound from the {token} route segment. Laravel's default reset-link
     * notification appends the recipient's email as a `?email=` query
     * string param (since a `password.reset` named route exists), so we
     * pre-fill it from there when present.
     */
    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $validated = $this->validate();

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (User $user) use ($validated): void {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('status', __('Your password has been reset. You can now log in.'));

        $this->redirect(route('login'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password');
    }
}
