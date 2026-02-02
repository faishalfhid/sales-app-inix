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

    // Method untuk kalkulasi total cost
    public static function calculateTotalCost(Get $get, $scenarioId = null): int
    {
        if (!$scenarioId) {
            $scenarioId = $get('scenario_id');
        }

        if (!$scenarioId) {
            return 0;
        }

        $components = CostComponent::query()
            ->whereHas('scenarioRules', fn($q) => 
                $q->where('scenario_id', $scenarioId)
            )
            ->get();

        $total = 0;
        foreach ($components as $component) {
            $unit = (int) ($get("unit_{$component->id}") ?? 0);
            $unitCost = (int) ($get("unit_cost_{$component->id}") ?? 0);
            $total += $unit * $unitCost;
        }

        return $total;
    }

    // Method untuk kalkulasi revenue
    public static function calculateRevenue(Get $get): int
    {
        $participantCount = (int) ($get('participant_count') ?? 0);
        $pricePerParticipant = (int) ($get('price_per_participant') ?? 0);
        $discount = (int) ($get('discount') ?? 0);

        return ($participantCount * $pricePerParticipant) - $discount;
    }

    // Method untuk kalkulasi net profit
    public static function calculateNetProfit(Get $get, $scenarioId = null): int
    {
        $revenue = self::calculateRevenue($get);
        $totalCost = self::calculateTotalCost($get, $scenarioId);

        return $revenue - $totalCost;
    }

    // Schema untuk summary section
    public static function getSummarySchema(): array
    {
        return [
            Forms\Components\Section::make('Ringkasan Keuangan')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('total_cost_display')
                                ->label('Total Biaya')
                                ->content(function (Get $get) {
                                    $total = self::calculateTotalCost($get);
                                    return 'Rp ' . number_format($total, 0, ',', '.');
                                })
                                ->live(),

                            Forms\Components\Placeholder::make('revenue_display')
                                ->label('Total Revenue')
                                ->content(function (Get $get) {
                                    $revenue = self::calculateRevenue($get);
                                    return 'Rp ' . number_format($revenue, 0, ',', '.');
                                })
                                ->live(),

                            Forms\Components\Placeholder::make('net_profit_display')
                                ->label('Net Profit')
                                ->content(function (Get $get) {
                                    $profit = self::calculateNetProfit($get);
                                    $color = $profit >= 0 ? 'success' : 'danger';
                                    $symbol = $profit >= 0 ? '+' : '';
                                    
                                    return new \Illuminate\Support\HtmlString(
                                        '<span class="text-' . $color . '-600 font-bold text-lg">' .
                                        $symbol . 'Rp ' . number_format($profit, 0, ',', '.') .
                                        '</span>'
                                    );
                                })
                                ->live(),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('profit_margin_display')
                                ->label('Profit Margin')
                                ->content(function (Get $get) {
                                    $revenue = self::calculateRevenue($get);
                                    $profit = self::calculateNetProfit($get);
                                    
                                    if ($revenue == 0) {
                                        return '0%';
                                    }
                                    
                                    $margin = ($profit / $revenue) * 100;
                                    $color = $margin >= 0 ? 'success' : 'danger';
                                    
                                    return new \Illuminate\Support\HtmlString(
                                        '<span class="text-' . $color . '-600 font-semibold">' .
                                        number_format($margin, 2) . '%' .
                                        '</span>'
                                    );
                                })
                                ->live(),

                            Forms\Components\Placeholder::make('price_per_participant_display')
                                ->label('Harga per Peserta')
                                ->content(function (Get $get) {
                                    $price = (int) ($get('price_per_participant') ?? 0);
                                    return 'Rp ' . number_format($price, 0, ',', '.');
                                })
                                ->live(),

                            Forms\Components\Placeholder::make('cost_per_participant_display')
                                ->label('Biaya per Peserta')
                                ->content(function (Get $get) {
                                    $participantCount = (int) ($get('participant_count') ?? 0);
                                    $totalCost = self::calculateTotalCost($get);
                                    
                                    if ($participantCount == 0) {
                                        return 'Rp 0';
                                    }
                                    
                                    $costPerParticipant = $totalCost / $participantCount;
                                    return 'Rp ' . number_format($costPerParticipant, 0, ',', '.');
                                })
                                ->live(),
                        ]),
                ])
                ->collapsible()
                ->collapsed(false),
        ];
    }

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
                        ->live(onBlur: true),

                    Forms\Components\TextInput::make("unit_cost_{$component->id}")
                        ->label('Harga Satuan')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->live(onBlur: true),

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

    public static function costCategoriesForScenario($scenarioId = null)
    {
        if (!$scenarioId) {
            return collect([]);
        }

        $componentIds = CostComponent::query()
            ->whereHas('scenarioRules', fn($q) => 
                $q->where('scenario_id', $scenarioId)
            )
            ->pluck('id');

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
                            ->required()
                            ->live(onBlur: true),

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
                            ->required()
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('discount')
                            ->label('Diskon')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->live(onBlur: true),

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

                // Summary Section
                ...self::getSummarySchema(),

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