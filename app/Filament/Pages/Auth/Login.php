<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('m1.login_identity'))
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $identity = (string) $data['email'];
        $identityField = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $identityField => $identity,
            'password' => (string) $data['password'],
            'is_active' => true,
        ];
    }
}
