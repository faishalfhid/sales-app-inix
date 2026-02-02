<?php

namespace App\Filament\Resources\TrainingClassResource\Pages;

use App\Filament\Resources\TrainingClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingClass extends EditRecord
{
    protected static string $resource = TrainingClassResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load cost details dan populate ke form fields
        $this->record->load('costDetails.costComponent.category');
        
        foreach ($this->record->costDetails as $detail) {
            $componentId = $detail->cost_component_id;
            $data["unit_{$componentId}"] = $detail->unit;
            $data["unit_cost_{$componentId}"] = $detail->unit_cost;
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ambil semua cost components
        $components = \App\Models\CostComponent::all();
        
        // Simpan untuk after save
        $this->costDetailsData = [];
        
        foreach ($components as $component) {
            $unit = $data["unit_{$component->id}"] ?? 0;
            $unitCost = $data["unit_cost_{$component->id}"] ?? 0;
            
            $this->costDetailsData[$component->id] = [
                'unit' => $unit,
                'unit_cost' => $unitCost,
            ];
            
            // Bersihkan data yang tidak perlu disimpan ke model utama
            unset($data["unit_{$component->id}"]);
            unset($data["unit_cost_{$component->id}"]);
            unset($data["cost_component_id_{$component->id}"]);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Update atau create cost details
        if (!empty($this->costDetailsData)) {
            foreach ($this->costDetailsData as $componentId => $detailData) {
                $this->record->costDetails()->updateOrCreate(
                    ['cost_component_id' => $componentId],
                    $detailData
                );
            }
        }
        
        // Recalculate
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