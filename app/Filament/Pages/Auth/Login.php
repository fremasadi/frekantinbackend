<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Livewire\Auth\Login as BasePage;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    // Ganti view default dengan view kustom Anda
    protected static string $view = 'filament.pages.auth.custom-login';

    // Kustomisasi form login tanpa remember me
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                // Remember me dihapus sesuai permintaan
            ])
            ->statePath('data');
    }

    // Opsional: Kustomisasi tampilan email field
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->extraAttributes(['class' => 'custom-email-input']);
    }

    // Opsional: Kustomisasi tampilan password field
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->required()
            ->extraAttributes(['class' => 'custom-password-input']);
    }
    
    /**
     * Mencegah rendering dengan card atau container default
     */
    protected function hasFullWidthFormContainer(): bool
    {
        return true;
    }
}