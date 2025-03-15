<?php

namespace App\Filament\Resources\TableNumberResource\Pages;

use App\Filament\Resources\TableNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTableNumber extends EditRecord
{
    protected static string $resource = TableNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
