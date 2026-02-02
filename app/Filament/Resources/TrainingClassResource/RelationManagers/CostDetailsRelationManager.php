<?php

namespace App\Filament\Resources\TrainingClassResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\CostComponent;

class CostDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'costDetails';
    
    protected static ?string $title = 'Detail Biaya';
    
    protected static ?string $recordTitleAttribute = 'id';
    
    // TAMBAHKAN INI: Specify model class explicitly
    protected static ?string $model = \App\Models\ClassCostDetail::class;

    public function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Select::make('cost_component_id')
                ->label('Komponen Biaya')
                ->required()
                ->searchable()
                ->preload()
                ->options(function (RelationManager $livewire): array {
                    $scenarioId = $livewire->ownerRecord?->scenario_id;

                    if (! $scenarioId) {
                        return [];
                    }

                    return CostComponent::query()
                        ->whereHas('scenarioRules', function ($query) use ($scenarioId) {
                            $query->where('scenario_id', $scenarioId)
                                ->where('is_required', true);
                        })
                        ->with('category')
                        ->orderBy('name')
                        ->get()
                        ->groupBy(fn ($component) => $component->category?->name ?? 'Tanpa Kategori')
                        ->map(fn ($components) => $components->pluck('name', 'id')->toArray())
                        ->toArray();
                }),

            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\TextInput::make('period')
                        ->label('Periode')
                        ->numeric()
                        ->default(0)
                        ->live(), // penting untuk update placeholder [web:77]

                    Forms\Components\TextInput::make('unit')
                        ->label('Unit (Satuan Waktu)')
                        ->numeric()
                        ->default(0)
                        ->live(), // penting untuk update placeholder [web:77]

                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantity (Satuan Jumlah)')
                        ->numeric()
                        ->default(0)
                        ->live(), // penting untuk update placeholder [web:77]
                ]),

            Forms\Components\TextInput::make('unit_cost')
                ->label('Biaya per Unit')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->live(onBlur: true),

            Forms\Components\Placeholder::make('subtotal_preview')
                ->label('Preview Subtotal')
                ->content(function (Forms\Get $get) {
                    $period = (int) $get('period');
                    $unit = (int) $get('unit');
                    $quantity = (int) $get('quantity');
                    $unitCost = (float) $get('unit_cost');

                    $subtotal = $period * $unit * $quantity * $unitCost;

                    return 'Rp ' . number_format($subtotal, 0, ',', '.');
                }),

            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull(),
        ]);
}

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            // TAMBAHKAN INI: Eager load relasi untuk performa
            ->modifyQueryUsing(fn ($query) => $query->with(['costComponent.category']))
            ->columns([
                Tables\Columns\TextColumn::make('costComponent.category.name')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('costComponent.name')
                    ->label('Komponen')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('costComponent.nature')
                    ->label('Sifat')
                    ->badge()
                    ->colors([
                        'primary' => 'R',
                        'success' => 'L',
                    ])
                    ->formatStateUsing(fn (string $state): string => $state === 'R' ? 'Real Cost' : 'Pass Cost'),
                    
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->alignCenter()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->alignCenter()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->alignCenter()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('costComponent.category', 'name')
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('nature')
                    ->label('Sifat Biaya')
                    ->options([
                        'R' => 'Real Cost (Inix Bayar)',
                        'L' => 'Pass Cost (Client Bayar)',
                    ])
                    ->query(function ($query, $state) {
                        if ($state && isset($state['value'])) {
                            $query->whereHas('costComponent', function ($q) use ($state) {
                                $q->where('nature', $state['value']);
                            });
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto calculate subtotal before saving
                        $data['subtotal'] = ($data['period'] ?? 0) * 
                                          ($data['unit'] ?? 0) * 
                                          ($data['quantity'] ?? 0) * 
                                          ($data['unit_cost'] ?? 0);
                        return $data;
                    })
                    ->after(function () {
                        // Recalculate training class totals
                        $this->getOwnerRecord()->recalculate();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['subtotal'] = ($data['period'] ?? 0) * 
                                          ($data['unit'] ?? 0) * 
                                          ($data['quantity'] ?? 0) * 
                                          ($data['unit_cost'] ?? 0);
                        return $data;
                    })
                    ->after(function () {
                        $this->getOwnerRecord()->recalculate();
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $this->getOwnerRecord()->recalculate();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function () {
                            $this->getOwnerRecord()->recalculate();
                        }),
                ]),
            ])
            ->emptyStateHeading('Belum ada detail biaya')
            ->emptyStateDescription('Klik tombol "Create" untuk menambahkan komponen biaya')
            ->emptyStateIcon('heroicon-o-currency-dollar');
    }
}