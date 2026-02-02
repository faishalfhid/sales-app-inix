<?php

namespace App\Filament\Resources\CostComponentResource\Pages;

use App\Filament\Resources\CostComponentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCostComponents extends ListRecords
{
    protected static string $resource = CostComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
