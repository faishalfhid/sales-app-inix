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

class TrainingClassResource extends Resource
{
    protected static ?string $model = TrainingClass::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Training Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $label = 'Kelas Pelatihan';
    protected static ?string $navigationLabel = 'Kelas Pelatihan';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\Select::make('scenario_id')
                            ->relationship('scenario', 'name')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $components = CostComponent::query()
                                    ->whereHas(
                                        'scenarioRules',
                                        fn($q) =>
                                        $q->where('scenario_id', $state)
                                            ->where('is_required', true)
                                    )
                                    ->get();

                                $set('costDetails', $components->map(fn($c) => [
                                    'cost_component_id' => $c->id,
                                    'component_name' => $c->name,
                                    'unit' => 0,
                                    'unit_cost' => 0,
                                ])->toArray());
                            }),


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
                        Forms\Components\Repeater::make('costDetails')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Forms\Components\Hidden::make('cost_component_id'),

                                Forms\Components\Placeholder::make('component')
                                    ->label('Komponen Biaya')
                                    ->content(function (Forms\Get $get, $record) {
                                        if ($record) {
                                            return $record->costComponent?->name ?? '-';
                                        }

                                        // mode create / setelah ganti scenario
                                        if ($get('cost_component_id')) {
                                            return \App\Models\CostComponent::find($get('cost_component_id'))?->name ?? '-';
                                        }

                                        return '-';
                                    }),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('unit')
                                            ->numeric()
                                            ->default(0)
                                            ->live(),

                                        Forms\Components\TextInput::make('unit_cost')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required()
                                            ->live(),
                                    ]),

                                Forms\Components\Placeholder::make('subtotal_preview')
                                    ->label('Preview Subtotal')
                                    ->content(function (Forms\Get $get) {
                                        $unit = (int) ($get('unit') ?: 0);
                                        $unitCost = (float) ($get('unit_cost') ?: 0);

                                        $subtotal = $unit * $unitCost;

                                        return 'Rp ' . number_format($subtotal, 0, ',', '.');
                                    })
                                    ->live(),
                            ])
                            ->columns(1)
                            ->disableItemCreation()
                            ->disableItemDeletion(),
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
