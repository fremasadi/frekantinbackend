<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getTitle(): string
    {
        return 'Detail Order';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    // Pilih salah satu cara di bawah ini:

    // Cara 1 - Menonaktifkan seluruh save actions
    protected function hasSaveActions(): bool
    {
        return false;
    }

    // Atau Cara 2 - Mengosongkan form actions
    protected function getFormActions(): array
    {
        return [];
    }
}