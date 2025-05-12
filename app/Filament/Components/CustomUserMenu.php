<?php

namespace App\Filament\Components;

use Filament\Navigation\MenuItem;
use Filament\Navigation\UserMenu;

class CustomUserMenu extends UserMenu
{
    public function getMenuItems(): array
    {
        return [
            MenuItem::make('Keluar')
                ->url(route('filament.auth.logout'))
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->color('danger'),
        ];
    }

    public function getUserName(): string
    {
        return ''; // Kosongkan nama
    }

    public function getUserAvatarUrl(): ?string
    {
        return null; // Hilangkan avatar
    }
}
