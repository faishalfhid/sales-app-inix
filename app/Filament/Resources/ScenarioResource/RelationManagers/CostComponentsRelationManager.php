<?php

namespace App\Filament\Resources\ScenarioResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class CostComponentsRelationManager extends RelationManager
{
    protected static string $relationship = 'costComponents';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Komponen Biaya')->searchable(),
                Tables\Columns\IconColumn::make('pivot.is_required')->boolean()->label('Required'),
                Tables\Columns\TextColumn::make('pivot.notes')->label('Catatan')->wrap(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(), // pilih cost component yang akan di-attach
                        Forms\Components\Toggle::make('is_required')
                            ->label('Required')
                            ->default(true),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3),
                    ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),

                // Optional: edit pivot (is_required/notes) via action custom
                Tables\Actions\Action::make('editPivot')
                    ->label('Edit Pivot')
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(fn ($record) => [
                        'is_required' => (bool) $record->pivot->is_required,
                        'notes' => $record->pivot->notes,
                    ])
                    ->form([
                        Forms\Components\Toggle::make('is_required')->label('Required'),
                        Forms\Components\Textarea::make('notes')->label('Catatan')->rows(3),
                    ])
                    ->action(function (array $data, $record) {
                        // update kolom pivot untuk pasangan scenario-cost_component ini
                        $this->getOwnerRecord()
                            ->costComponents()
                            ->updateExistingPivot($record->getKey(), [
                                'is_required' => (bool) ($data['is_required'] ?? false),
                                'notes' => $data['notes'] ?? null,
                            ]);
                    }),
            ]);
    }
}
