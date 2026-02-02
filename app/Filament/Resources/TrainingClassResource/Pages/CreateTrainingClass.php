<?php

namespace App\Filament\Resources\TrainingClassResource\Pages;

use App\Filament\Resources\TrainingClassResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms;
use Filament\Forms\Components\Tabs;

class CreateTrainingClass extends CreateRecord
{
    use HasWizard;
    
    protected static string $resource = TrainingClassResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ambil semua cost components
        $components = \App\Models\CostComponent::all();
        
        // Simpan data cost details untuk diproses setelah record dibuat
        $this->costDetailsData = [];
        
        foreach ($components as $component) {
            $unit = $data["unit_{$component->id}"] ?? 0;
            $unitCost = $data["unit_cost_{$component->id}"] ?? 0;
            
            // Simpan semua, bahkan yang 0, untuk konsistensi
            $this->costDetailsData[] = [
                'cost_component_id' => $component->id,
                'unit' => $unit,
                'unit_cost' => $unitCost,
            ];
            
            // Bersihkan dari data utama
            unset($data["unit_{$component->id}"]);
            unset($data["unit_cost_{$component->id}"]);
            unset($data["cost_component_id_{$component->id}"]);
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Simpan cost details
        if (!empty($this->costDetailsData)) {
            foreach ($this->costDetailsData as $detailData) {
                $this->record->costDetails()->create($detailData);
            }
        }
        
        // Recalculate
        $this->record->load('costDetails.costComponent');
        $this->record->recalculate();
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Informasi Dasar')
                ->schema([
                    Forms\Components\Select::make('scenario_id')
                        ->relationship('scenario', 'name')
                        ->required()
                        ->reactive()
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
                        ->required(),
                        
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
                        ->required(),
                        
                    Forms\Components\TextInput::make('discount')
                        ->label('Diskon')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),
                        
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'proposed' => 'Proposed',
                            'approved' => 'Approved',
                        ])
                        ->default('draft')
                        ->required(),
                ])
                ->columns(2),
                
            Step::make('Detail Biaya')
                ->schema([
                    Tabs::make('Cost Categories')
                        ->tabs(
                            TrainingClassResource::costCategories()->map(function ($category) {
                                return Tabs\Tab::make($category->name)
                                    ->badge(function (Forms\Get $get) use ($category) {
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
                            })->toArray()
                        ),
                ]),
        ];
    }
}