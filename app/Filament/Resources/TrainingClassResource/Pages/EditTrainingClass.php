<?php

namespace App\Filament\Resources\TrainingClassResource\Pages;

use App\Filament\Resources\TrainingClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\CostComponent;
use Filament\Notifications\Notification;

class EditTrainingClass extends EditRecord
{
    protected static string $resource = TrainingClassResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
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
        $user = auth()->user();
        $originalStatus = $this->record->getOriginal('status');
        $newStatus = $data['status'] ?? $originalStatus;

        // Validasi berdasarkan role
        if ($user->isStaff()) {
            // Staff: hanya bisa set draft atau proposed
            if (in_array($originalStatus, ['approved', 'running', 'completed', 'cancelled'])) {
                $data['status'] = $originalStatus;
                
                Notification::make()
                    ->warning()
                    ->title('Status Tidak Dapat Diubah')
                    ->body('Status sudah diproses oleh manajemen dan tidak dapat diubah.')
                    ->send();
            }
            elseif (!in_array($newStatus, ['draft', 'proposed'])) {
                $data['status'] = 'proposed';
                
                Notification::make()
                    ->warning()
                    ->title('Status Dibatasi')
                    ->body('Anda hanya dapat mengatur status Draft atau Proposed.')
                    ->send();
            }
        }
        elseif ($user->canApprove() && !$user->isAdmin()) {
            // Direktur/GM: tidak bisa ubah status lewat form, harus pakai button Approve/Revise
            $data['status'] = $originalStatus;
        }
        // Admin: bebas mengubah status

        $scenarioId = $data['scenario_id'] ?? $this->record->scenario_id;
        
        if (!$scenarioId) {
            return $data;
        }

        $components = CostComponent::query()
            ->whereHas('scenarioRules', fn($q) => 
                $q->where('scenario_id', $scenarioId)
            )
            ->get();
        
        $this->costDetailsData = [];
        
        foreach ($components as $component) {
            $unit = $data["unit_{$component->id}"] ?? 0;
            $unitCost = $data["unit_cost_{$component->id}"] ?? 0;
            
            $this->costDetailsData[$component->id] = [
                'unit' => $unit,
                'unit_cost' => $unitCost,
            ];
            
            unset($data["unit_{$component->id}"]);
            unset($data["unit_cost_{$component->id}"]);
            unset($data["cost_component_id_{$component->id}"]);
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        $scenarioId = $this->record->scenario_id;
        $validComponentIds = CostComponent::query()
            ->whereHas('scenarioRules', fn($q) => 
                $q->where('scenario_id', $scenarioId)
            )
            ->pluck('id');
        
        $this->record->costDetails()
            ->whereNotIn('cost_component_id', $validComponentIds)
            ->delete();

        if (!empty($this->costDetailsData)) {
            foreach ($this->costDetailsData as $componentId => $detailData) {
                $this->record->costDetails()->updateOrCreate(
                    ['cost_component_id' => $componentId],
                    $detailData
                );
            }
        }
        
        $this->record->load('costDetails.costComponent');
        $this->record->recalculate();
    }

    protected function getRedirectUrl(): string
    {
        return TrainingClassResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $actions = [];

        // Actions untuk Direktur/GM (Approve & Revise)
        if ($user->canApprove() && !$user->isAdmin()) {
            if ($this->record->status === 'proposed') {
                $actions[] = Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('approval_notes')
                            ->label('Catatan Approval (Opsional)')
                            ->rows(3),
                    ])
                    ->action(function (array $data) {
                        $this->record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Training Class Approved')
                            ->success()
                            ->body('Training class telah disetujui.')
                            ->send();

                        $this->redirect(TrainingClassResource::getUrl('index'));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Training Class')
                    ->modalDescription('Training class akan disetujui dan menunggu admin untuk mengatur jadwal.')
                    ->modalSubmitActionLabel('Ya, Approve');

                $actions[] = Actions\Action::make('revise')
                    ->label('Revise')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('approval_notes')
                            ->label('Catatan Revisi')
                            ->required()
                            ->rows(3)
                            ->helperText('Jelaskan apa yang perlu diperbaiki'),
                    ])
                    ->action(function (array $data) {
                        $this->record->update([
                            'status' => 'draft',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'],
                        ]);

                        Notification::make()
                            ->title('Training Class Needs Revision')
                            ->warning()
                            ->body('Training class dikembalikan ke staff untuk revisi.')
                            ->send();

                        $this->redirect(TrainingClassResource::getUrl('index'));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Revise Training Class')
                    ->modalDescription('Training class akan dikembalikan ke staff untuk diperbaiki.')
                    ->modalSubmitActionLabel('Ya, Minta Revisi');
            }
        }

        // Delete action
        if ($user->isAdmin() || $this->record->status === 'draft') {
            $actions[] = Actions\DeleteAction::make();
        }

        return $actions;
    }
}