<?php

namespace App\Filament\Resources\TrainingClassResource\Pages;

use App\Filament\Resources\TrainingClassResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use App\Models\CostComponent;

class CreateTrainingClass extends CreateRecord
{
    use HasWizard;

    protected static string $resource = TrainingClassResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $scenarioId = $data['scenario_id'] ?? null;

        if (!$scenarioId) {
            return $data;
        }

        $components = CostComponent::query()
            ->whereHas(
                'scenarioRules',
                fn($q) =>
                $q->where('scenario_id', $scenarioId)
            )
            ->get();

        $this->costDetailsData = [];

        foreach ($components as $component) {
            $unit = $data["unit_{$component->id}"] ?? 0;
            $unitCost = $data["unit_cost_{$component->id}"] ?? 0;

            $this->costDetailsData[] = [
                'cost_component_id' => $component->id,
                'unit' => $unit,
                'unit_cost' => $unitCost,
            ];

            unset($data["unit_{$component->id}"]);
            unset($data["unit_cost_{$component->id}"]);
            unset($data["cost_component_id_{$component->id}"]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (!empty($this->costDetailsData)) {
            foreach ($this->costDetailsData as $detailData) {
                $this->record->costDetails()->create($detailData);
            }
        }

        $this->record->load('costDetails.costComponent');
        $this->record->recalculate();
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Informasi Dasar')
                ->schema([
                    Forms\Components\Select::make('scenario_id')
                        ->label('Skenario Pelatihan')
                        ->relationship('scenario', 'name')
                        ->required()
                        ->live()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('sales_name')
                        ->label('Nama Sales')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('material')
                        ->label('Materi')
                        ->maxLength(200),

                    Forms\Components\TextInput::make('customer')
                        ->label('Customer')
                        ->maxLength(200)
                        ->required(),
                ])
                ->columns(2),

            Step::make('Detail Pelatihan')
                ->schema([
                    Forms\Components\TextInput::make('training_days')
                        ->label('Jumlah Hari Pelatihan')
                        ->numeric()
                        ->default(0)
                        ->required(),

                    Forms\Components\TextInput::make('admin_days')
                        ->label('Jumlah Hari Administrasi')
                        ->numeric()
                        ->default(0),

                    Forms\Components\TextInput::make('participant_count')
                        ->label('Jumlah Peserta')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->live(onBlur: true),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai'),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai'),
                ])
                ->columns(2),

            Step::make('Harga & Status')
                ->schema([
                    Forms\Components\TextInput::make('price_per_participant')
                        ->label('Harga Real/Peserta')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true),

                    Forms\Components\TextInput::make('discount')
                        ->label('Diskon')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->live(onBlur: true),

                    // Status untuk Direktur/GM/Admin
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'proposed' => 'Proposed',
                            'approved' => 'Approved',
                        ])
                        ->default('draft')
                        ->required()
                        ->visible(fn() => auth()->user()->canApprove() || auth()->user()->isAdmin()),

                    // Status untuk Staff (hanya draft dan proposed)
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'proposed' => 'Proposed (Kirim untuk Approval)',
                        ])
                        ->default('draft')
                        ->required()
                        ->helperText('Pilih "Proposed" untuk mengirim ke Direktur/GM untuk approval')
                        ->visible(fn() => !auth()->user()->canApprove() && !auth()->user()->isAdmin()),
                ])
                ->columns(2),

            Step::make('Detail Biaya')
                ->schema([
                    Forms\Components\Placeholder::make('scenario_warning')
                        ->label('')
                        ->content('Pastikan Anda telah memilih Skenario Pelatihan di Step 1')
                        ->visible(fn(Forms\Get $get) => !$get('scenario_id')),

                    Tabs::make('Cost Categories')
                        ->visible(fn(Forms\Get $get) => $get('scenario_id') !== null)
                        ->tabs(function (Forms\Get $get) {
                            $scenarioId = $get('scenario_id');

                            if (!$scenarioId) {
                                return [];
                            }

                            return TrainingClassResource::costCategoriesForScenario($scenarioId)
                                ->map(function ($category) use ($get) {
                                    return Tabs\Tab::make($category->name)
                                        ->badge(function () use ($category, $get) {
                                            $total = 0;
                                            foreach ($category->costComponents as $component) {
                                                $unit = (int) ($get("unit_{$component->id}") ?? 0);
                                                $unitCost = (int) ($get("unit_cost_{$component->id}") ?? 0);
                                                $total += $unit * $unitCost;
                                            }

                                            return $total > 0
                                                ? 'Rp ' . number_format($total, 0, ',', '.')
                                                : null;
                                        })
                                        ->schema([
                                            Forms\Components\Grid::make(1)
                                                ->schema(
                                                    collect($category->costComponents)->map(function ($component) {
                                                        return Forms\Components\Group::make()
                                                            ->schema(TrainingClassResource::costDetailSchemaForComponent($component))
                                                            ->columnSpanFull();
                                                    })->toArray()
                                                )
                                        ]);
                                })->toArray();
                        }),
                ]),

            Step::make('Ringkasan')
                ->schema(TrainingClassResource::getSummarySchema())
                ->columns(1),
        ];
    }
}