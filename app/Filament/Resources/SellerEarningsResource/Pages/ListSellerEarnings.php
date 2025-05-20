<?php

namespace App\Filament\Resources\SellerEarningsResource\Pages;

use App\Filament\Resources\SellerEarningsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSellerEarnings extends ListRecords
{
    protected static string $resource = SellerEarningsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
