<?php

namespace App\Filament\Resources\ImageContentResource\Pages;

use App\Filament\Resources\ImageContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImageContent extends EditRecord
{
    protected static string $resource = ImageContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
