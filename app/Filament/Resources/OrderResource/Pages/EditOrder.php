<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return 'Detail Order';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    // Solusi 1: Nonaktifkan seluruh save actions
    protected function hasSaveActions(): bool
    {
        return false;
    }

    // Solusi 2: Kosongkan form actions (lebih eksplisit)
    protected function getFormActions(): array
    {
        return [];
    }

    // Solusi 3: Kombinasi keduanya untuk memastikan
    protected function getForms(): array
    {
        return [
            'form' => $this->form(static::getResource()::form(
                $this->makeForm()
                    ->model($this->getRecord())
                    ->statePath('data')
                    ->operation('edit')
                    ->disabled() // Menonaktifkan seluruh form
            )),
        ];
    }
}