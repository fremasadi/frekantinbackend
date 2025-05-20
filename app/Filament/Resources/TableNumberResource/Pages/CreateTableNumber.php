<?php

namespace App\Filament\Resources\TableNumberResource\Pages;

use App\Filament\Resources\TableNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTableNumber extends CreateRecord
{
    protected static string $resource = TableNumberResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
