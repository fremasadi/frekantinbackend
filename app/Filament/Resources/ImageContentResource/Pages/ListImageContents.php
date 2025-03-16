<?php

namespace App\Filament\Resources\ImageContentResource\Pages;

use App\Filament\Resources\ImageContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImageContents extends ListRecords
{
    protected static string $resource = ImageContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
