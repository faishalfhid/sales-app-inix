<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrainingClassResource\Pages;
use App\Filament\Resources\TrainingClassResource\RelationManagers;
use App\Models\CostComponent;
use App\Models\TrainingClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Repeater;

class TrainingClassResource extends Resource
{
    protected static ?string $model = TrainingClass::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Training Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $label = 'Kelas Pelatihan';
    protected static ?string $navigationLabel = 'Kelas Pelatihan';

    public static function costDetailSchemaForComponent($component): array
    {
        return [
            Forms\Components\Placeholder::make("component_name_{$component->id}")
                ->label('Komponen Biaya')
                ->content($component->name),
            
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\TextInput::make("unit_{$component->id}")
                        ->label('Unit')
                        ->numeric()
                        ->default(0)
                        ->live(),

                    Forms\Components\TextInput::make("unit_cost_{$component->id}")
                        ->label('Harga Satuan')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->live(),

                    Forms\Components\Placeholder::make("subtotal_{$component->id}")
                        ->label('Subtotal')
                        ->content(
                            fn(Forms\Get $get) =>
                            'Rp ' . number_format(
                                ((int) ($get("unit_{$component->id}") ?? 0)) * 
                                ((int) ($get("unit_cost_{$component->id}") ?? 0)),
                                0,
                                ',',
                                '.'
                            )
                        )
                        ->live(),
                ]),
            
            Forms\Components\Hidden::make("cost_component_id_{$component->id}")
                ->default($component->id),
        ];
    }

    // Mendapatkan categories dengan cost components yang sesuai scenario
    public static function costCategoriesForScenario($scenarioId = null)
    {
        if (!$scenarioId) {
            return collect([]);
        }

        // Ambil cost components yang terkait dengan scenario
        $componentIds = CostComponent::query()
            ->whereHas('scenarioRules', fn($q) => 
                $q->where('scenario_id', $scenarioId)
            )
            ->pluck('id');

        // Ambil categories yang memiliki components dari scenario ini
        return \App\Models\Category::with(['costComponents' => function($query) use ($componentIds) {
            $query->whereIn('id', $componentIds);
        }])
        ->whereHas('costComponents', fn($q) => 
            $q->whereIn('id', $componentIds)
        )
        ->get();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\Select::make('scenario_id')
                            ->label('Skenario Pelatihan')
                            ->relationship('scenario', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Reset semua cost detail fields ketika scenario berubah
                                $components = CostComponent::all();
                                foreach ($components as $component) {
                                    $set("unit_{$component->id}", 0);
                                    $set("unit_cost_{$component->id}", 0);
                                }
                            })
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('sales_name')
                            ->label('Nama Sales')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('material')
                            ->label('Materi')
                            ->maxLength(200),

                        Forms\Components\TextInput::make('customer')
                            ->label('Customer')
                            ->maxLength(200),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detail Pelatihan')
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

                Forms\Components\Section::make('Harga & Revenue')
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
                                'running' => 'Running',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Detail Biaya')
                    ->schema([
                        Forms\Components\Placeholder::make('scenario_warning')
                            ->label('')
                            ->content('Pilih Skenario Pelatihan terlebih dahulu untuk mengisi detail biaya')
                            ->visible(fn(Get $get) => !$get('scenario_id')),

                        Tabs::make('Cost Categories')
                            ->visible(fn(Get $get) => $get('scenario_id') !== null)
                            ->tabs(function (Get $get) {
                                $scenarioId = $get('scenario_id');
                                
                                if (!$scenarioId) {
                                    return [];
                                }

                                return self::costCategoriesForScenario($scenarioId)
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
                                                                ->schema(self::costDetailSchemaForComponent($component))
                                                                ->columnSpanFull();
                                                        })->toArray()
                                                    )
                                            ]);
                                    })->toArray();
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('material')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('scenario.name')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('participant_count')
                    ->label('Peserta')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Revenue')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_profit')
                    ->label('Net Profit')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn($state) => $state >= 0 ? 'success' : 'danger'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'proposed',
                        'success' => 'approved',
                        'primary' => 'running',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scenario')
                    ->relationship('scenario', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'proposed' => 'Proposed',
                        'approved' => 'Approved',
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\CostDetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainingClasses::route('/'),
            'create' => Pages\CreateTrainingClass::route('/create'),
            'edit' => Pages\EditTrainingClass::route('/{record}/edit'),
        ];
    }
}