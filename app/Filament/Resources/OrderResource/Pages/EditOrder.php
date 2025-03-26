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


    protected function hasSaveActions(): bool
    {
        return false;
    }

    
}