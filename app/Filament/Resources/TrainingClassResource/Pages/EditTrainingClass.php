<?php

namespace App\Filament\Resources\TrainingClassResource\Pages;

use App\Filament\Resources\TrainingClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingClass extends EditRecord
{
    protected static string $resource = TrainingClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
