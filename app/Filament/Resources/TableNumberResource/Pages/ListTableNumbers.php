<?php

namespace App\Filament\Resources\TableNumberResource\Pages;

use App\Filament\Resources\TableNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTableNumbers extends ListRecords
{
    protected static string $resource = TableNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
