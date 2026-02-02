<?php

namespace App\Filament\Resources\TrainingClassResource\Pages;

use App\Filament\Resources\TrainingClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainingClasses extends ListRecords
{
    protected static string $resource = TrainingClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
