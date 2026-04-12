<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class CounterpartyLogin extends Login
{
    public ?string $ssoError = null;

    public function mount(): void
    {
        parent::mount();

        $ssoError = trim((string) request()->query('sso_error'));
        if ($ssoError !== '') {
            $this->ssoError = $ssoError;
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('sso_error')
                    ->label('')
                    ->content(fn (): ?string => $this->getSsoErrorMessage())
                    ->color('danger')
                    ->visible(fn (): bool => filled($this->getSsoErrorMessage())),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Логин')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'login' => $data['login'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    protected function getSsoErrorMessage(): ?string
    {
        if ($this->ssoError !== '403') {
            return null;
        }

        return '403: токен сквозной авторизации недействителен или истек. Авторизуйтесь заново.';
    }
}
