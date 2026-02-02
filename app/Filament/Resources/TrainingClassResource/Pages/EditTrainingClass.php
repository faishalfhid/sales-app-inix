<?php

namespace App\Filament\Resources\TrainingClassResource\Pages;

use App\Filament\Resources\TrainingClassResource;
use Filament\Actions;
use Filament\Pages\Concerns\HasRoutes;
use Filament\Resources\Pages\EditRecord;

class EditTrainingClass extends EditRecord
{
    protected static string $resource = TrainingClassResource::class;

    protected function afterSave(): void
{
    $this->record->load('costDetails.costComponent');
    $this->record->recalculate();
}

    
    protected function getRedirectUrl(): string
    {
        return TrainingClassResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
