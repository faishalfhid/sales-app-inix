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
use Illuminate\Database\Eloquent\Builder;


class TrainingClassResource extends Resource
{
    protected static ?string $model = TrainingClass::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Training Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $label = 'Kelas Pelatihan';

    protected static ?string $pluralLabel = 'Kelas Pelatihan';
    protected static ?string $navigationLabel = 'Kelas Pelatihan';


    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->role === 'staff') {
            $query->where('sales_id', auth()->id());
        }

        return $query;
    }

    public static function parseRupiah(mixed $value): int
    {
        return (int) preg_replace('/\D/', '', $value ?? '0');
    }

    // Method untuk kalkulasi real cost dan pass cost
    protected static function calculateRealCost(Get $get): float
    {
        $scenarioId = $get('scenario_id');
        if (!$scenarioId)
            return 0;

        $components = CostComponent::query()
            ->whereHas('scenarioRules', fn($q) => $q->where('scenario_id', $scenarioId))
            ->where('nature', 'R')
            ->get();

        $total = 0;
        foreach ($components as $component) {
            $unit = (int) ($get("unit_{$component->id}") ?? 0);
            $unitCost = self::parseRupiah($get("unit_cost_{$component->id}"));
            $total += $unit * $unitCost;
        }

        return $total;
    }

    protected static function calculatePassCost(Get $get): float
    {
        $scenarioId = $get('scenario_id');
        if (!$scenarioId)
            return 0;

        $components = CostComponent::query()
            ->whereHas('scenarioRules', fn($q) => $q->where('scenario_id', $scenarioId))
            ->where('nature', 'L')
            ->get();

        $total = 0;
        foreach ($components as $component) {
            $unit = (int) ($get("unit_{$component->id}") ?? 0);
            $unitCost = self::parseRupiah($get("unit_cost_{$component->id}"));
            $total += $unit * $unitCost;
        }

        return $total;
    }

    public static function calculateTotalCost(Get $get, $scenarioId = null): int
    {
        if (!$scenarioId)
            $scenarioId = $get('scenario_id');
        if (!$scenarioId)
            return 0;

        $components = CostComponent::query()
            ->whereHas('scenarioRules', fn($q) => $q->where('scenario_id', $scenarioId))
            ->get();

        $total = 0;
        foreach ($components as $component) {
            $unit = (int) ($get("unit_{$component->id}") ?? 0);
            $unitCost = self::parseRupiah($get("unit_cost_{$component->id}"));
            $total += $unit * $unitCost;
        }

        return $total;
    }

    public static function calculateRevenue(Get $get): int
    {
        $participantCount = (int) ($get('participant_count') ?? 0);
        $pricePerParticipant = self::parseRupiah($get('price_per_participant'));
        $discount = self::parseRupiah($get('discount'));

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
                                    $price = self::parseRupiah($get('price_per_participant')); // ← ganti ini
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

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('real_cost_display')
                                ->label('Total Real Cost (R)')
                                ->content(function (Get $get) {
                                    $total = self::calculateRealCost($get);
                                    return 'Rp ' . number_format($total, 0, ',', '.');
                                })
                                ->live(),

                            Forms\Components\Placeholder::make('pass_cost_display')
                                ->label('Total Pass Cost (L)')
                                ->content(function (Get $get) {
                                    $total = self::calculatePassCost($get);
                                    return 'Rp ' . number_format($total, 0, ',', '.');
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

                    self::currencyInput("unit_cost_{$component->id}", 'Harga Satuan')
                        ->default(0)
                        ->live(onBlur: true),


                    Forms\Components\Placeholder::make("subtotal_{$component->id}")
                        ->label('Subtotal')
                        ->content(
                            fn(Forms\Get $get) =>
                            'Rp ' . number_format(
                                ((int) ($get("unit_{$component->id}") ?? 0)) *
                                self::parseRupiah($get("unit_cost_{$component->id}")),
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
            ->whereHas(
                'scenarioRules',
                fn($q) =>
                $q->where('scenario_id', $scenarioId)
            )
            ->pluck('id');

        return \App\Models\Category::with([
            'costComponents' => function ($query) use ($componentIds) {
                $query->whereIn('id', $componentIds);
            }
        ])
            ->whereHas(
                'costComponents',
                fn($q) =>
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
                            ->default(fn() => auth()->user()->name)
                            ->disabled()
                            ->dehydrated(false),

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
                        Forms\Components\Toggle::make('use_multiple_dates')
                            ->label('Tanggal lompat-lompat?')
                            ->helperText('Aktifkan untuk memilih beberapa tanggal tidak berurutan')
                            ->live()
                            ->afterStateHydrated(function ($component, $record) {
                                // Set state berdasarkan record saat form di-hydrate (edit mode)
                                if ($record) {
                                    $component->state(!empty($record->selected_dates));
                                }
                            })
                            ->default(false)
                            ->columnSpanFull(),

                        // ── MODE BIASA ──────────────────────────────────────────────
                        Forms\Components\TextInput::make('training_days')
                            ->label('Jumlah Hari Pelatihan')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->visible(fn(Get $get) => !$get('use_multiple_dates')),

                        Forms\Components\TextInput::make('admin_days')
                            ->label('Jumlah Hari Administrasi')
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->helperText('Dihitung otomatis dari tanggal mulai & selesai')
                            ->visible(fn(Get $get) => !$get('use_multiple_dates')),

                        Forms\Components\TextInput::make('participant_count')
                            ->label('Jumlah Peserta')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->live(onBlur: true),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->live(onBlur: true)
                            ->visible(fn(Get $get) => !$get('use_multiple_dates'))
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if (!empty($get('selected_dates')))
                                    return;
                                $end = $get('end_date');
                                if ($state && $end) {
                                    $days = \Carbon\Carbon::parse($state)
                                        ->diffInDays(\Carbon\Carbon::parse($end)) + 1;
                                    $set('admin_days', max(0, $days));
                                }
                            }),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->live(onBlur: true)
                            ->visible(fn(Get $get) => !$get('use_multiple_dates'))
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if (!empty($get('selected_dates')))
                                    return;
                                $start = $get('start_date');
                                if ($start && $state) {
                                    $days = \Carbon\Carbon::parse($start)
                                        ->diffInDays(\Carbon\Carbon::parse($state)) + 1;
                                    $set('admin_days', max(0, $days));
                                }
                            }),

                        // ── MODE MULTI-DATE ──────────────────────────────────────────
                        \App\Forms\Components\MultiDatePickerField::make('selected_dates')
                            ->label('Pilih Tanggal Pelatihan')
                            ->helperText('Klik tanggal untuk memilih/batal. Bisa pilih banyak tanggal.')
                            ->visible(fn(Get $get) => (bool) $get('use_multiple_dates'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('training_days')
                            ->label('Jumlah Hari Pelatihan')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->visible(fn(Get $get) => (bool) $get('use_multiple_dates')),

                        Forms\Components\TextInput::make('admin_days')
                            ->label('Jumlah Hari Administrasi')
                            ->numeric()
                            ->default(0)
                            ->readOnly()
                            ->helperText('Dihitung otomatis dari jumlah tanggal dipilih')
                            ->visible(fn(Get $get) => (bool) $get('use_multiple_dates')),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Harga & Revenue')
                    ->schema([
                        self::currencyInput('price_per_participant', 'Harga Real/Peserta', required: true)
                            ->live(onBlur: true),

                        self::currencyInput('discount', 'Diskon')
                            ->default(0)
                            ->live(onBlur: true),

                        // Status untuk Admin (semua status)
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'revision' => 'Revision', // ← TAMBAHKAN
                                'proposed' => 'Proposed',
                                'approved' => 'Approved',
                                'running' => 'Running',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required()
                            ->disabled(fn($record) => $record && $record->status === 'proposed')
                            ->dehydrated(true)
                            ->helperText(
                                fn($record) =>
                                $record && $record->status === 'proposed'
                                ? 'Gunakan tombol Approve/Revise untuk mengubah status'
                                : 'Kelola status kelas pelatihan'
                            )
                            ->visible(fn() => auth()->user()->isAdmin()),

                        // Status untuk Direktur/GM (hanya display, tidak bisa edit langsung)
                        Forms\Components\Placeholder::make('status_display_manager')
                            ->label('Status')
                            ->content(function ($record) {
                                if (!$record) {
                                    return '-';
                                }

                                $statusLabels = [
                                    'draft' => 'Draft',
                                    'revision' => 'Perlu Revisi', // ← TAMBAHKAN
                                    'proposed' => 'Menunggu Approval',
                                    'approved' => 'Disetujui',
                                    'running' => 'Berjalan',
                                    'completed' => 'Selesai',
                                    'cancelled' => 'Dibatalkan',
                                ];

                                $colors = [
                                    'draft' => 'gray',
                                    'revision' => 'danger', // ← TAMBAHKAN dengan warna merah
                                    'proposed' => 'warning',
                                    'approved' => 'success',
                                    'running' => 'darkGreen',
                                    'completed' => 'info',
                                    'cancelled' => 'danger',
                                ];

                                $status = $record->status;
                                $color = $colors[$status] ?? 'gray';
                                $label = $statusLabels[$status] ?? $status;

                                return new \Illuminate\Support\HtmlString(
                                    '<span class="inline-flex items-center gap-x-1.5 rounded-md px-3 py-1.5 text-sm font-semibold ring-1 ring-inset bg-' . $color . '-50 text-' . $color . '-700 ring-' . $color . '-600/20">' .
                                    $label .
                                    '</span>'
                                );
                            })
                            ->visible(fn() => auth()->user()->canApprove() && !auth()->user()->isAdmin()),

                        // Hidden field untuk Direktur/GM
                        Forms\Components\Hidden::make('status')
                            ->default(fn($record) => $record?->status ?? 'draft')
                            ->visible(fn() => auth()->user()->canApprove() && !auth()->user()->isAdmin()),

                        // Status untuk Staff (hanya draft & proposed)
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'proposed' => 'Proposed (Kirim untuk Approval)',
                            ])
                            ->default('draft')
                            ->required()
                            ->disabled(fn($record) => $record && !in_array($record->status, ['draft', 'revision', 'proposed'])) // ← TAMBAHKAN 'revision'
                            ->dehydrated(true)
                            ->helperText(function ($record) {
                                if ($record && !in_array($record->status, ['draft', 'revision', 'proposed'])) { // ← TAMBAHKAN 'revision'
                                    return 'Status tidak dapat diubah karena sudah ' . match ($record->status) {
                                        'approved' => 'disetujui',
                                        'running' => 'berjalan',
                                        'completed' => 'selesai',
                                        'cancelled' => 'dibatalkan',
                                        default => 'diproses'
                                    } . ' oleh manajemen.';
                                }
                                return 'Ubah ke "Proposed" untuk mengirim ke Direktur/GM untuk approval';
                            })
                            ->visible(fn() => auth()->user()->isStaff()),
                    ])
                    ->columns(3),

                // Summary Section
                ...self::getSummarySchema(),

                // Info approval/revision notes
                Forms\Components\Section::make('Catatan Manajemen')
                    ->schema([
                        Forms\Components\Placeholder::make('approval_info')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->approval_notes) {
                                    return null;
                                }

                                $color = $record->status === 'approved' ? 'success' : 'warning';
                                $title = $record->status === 'approved' ? 'Disetujui' : 'Perlu Revisi';

                                return new \Illuminate\Support\HtmlString(
                                    '<div class="rounded-lg border border-' . $color . '-200 bg-' . $color . '-50 p-4">' .
                                    '<div class="flex items-start">' .
                                    '<div>' .
                                    '<h4 class="font-semibold text-' . $color . '-900">' . $title . ' oleh ' . ($record->approver?->name ?? 'Manajemen') . '</h4>' .
                                    '<p class="text-sm text-' . $color . '-700 mt-1">' . $record->approval_notes . '</p>' .
                                    '<p class="text-xs text-' . $color . '-600 mt-2">Pada: ' . $record->approved_at?->format('d M Y') . '</p>' .
                                    '</div>' .
                                    '</div>' .
                                    '</div>'
                                );
                            }),
                    ])
                    ->visible(fn($record) => $record && $record->approval_notes)
                    ->columnSpanFull(),

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
                                                    $unitCost = TrainingClassResource::parseRupiah($get("unit_cost_{$component->id}"));
                                                    $total += $unit * $unitCost;
                                                }
                                                return $total > 0 ? 'Rp ' . number_format($total, 0, ',', '.') : null;
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
                        'danger' => 'revision', // ← TAMBAHKAN dengan warna merah
                        'warning' => 'proposed',
                        'success' => 'approved',
                        'darkGreen' => 'running',
                        'info' => 'completed',
                        'cancel' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scenario')
                    ->relationship('scenario', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'revision' => 'Revision',
                        'proposed' => 'Proposed',
                        'approved' => 'Approved',
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                // Approve Action - Hanya untuk Direktur/GM pada status proposed
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function ($record) {
                        $user = auth()->user();
                        return $record->status === 'proposed'
                            && $user->canApprove()
                            && !$user->isAdmin();
                    })
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Catatan Approval (Opsional)')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'] ?? null,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Training Class Approved')
                            ->success()
                            ->body('Training class untuk ' . $record->customer . ' telah disetujui.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Training Class')
                    ->modalDescription('Training class akan disetujui dan menunggu admin untuk mengatur jadwal.')
                    ->modalSubmitActionLabel('Ya, Approve'),

                // Revise Action - Hanya untuk Direktur/GM pada status proposed
                Tables\Actions\Action::make('revise')
                    ->label('Revise')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(function ($record) {
                        $user = auth()->user();
                        return $record->status === 'proposed'
                            && $user->canApprove()
                            && !$user->isAdmin();
                    })
                    ->form([
                        Forms\Components\Textarea::make('approval_notes')
                            ->label('Catatan Revisi')
                            ->required()
                            ->rows(3)
                            ->helperText('Jelaskan apa yang perlu diperbaiki'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'revision',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'approval_notes' => $data['approval_notes'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Training Class Needs Revision')
                            ->warning()
                            ->body('Training class dikembalikan ke staff untuk revisi.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Revise Training Class')
                    ->modalDescription('Training class akan dikembalikan ke staff untuk diperbaiki.')
                    ->modalSubmitActionLabel('Ya, Minta Revisi'),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => auth()->user()->isAdmin() || in_array($record->status, ['draft', 'revision'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->isAdmin()),
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

    public static function currencyInput(string $field, string $label, bool $required = false): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($field)
            ->label($label)
            ->required($required)
            ->numeric(false) // matikan numeric bawaan
            ->prefix('Rp')
            ->extraInputAttributes([
                'x-data' => '{}',
                'x-on:input' => '
                let raw = $event.target.value.replace(/\D/g, "");
                $event.target.value = raw === "" ? "" : parseInt(raw).toLocaleString("id-ID");
            ',
                'x-on:blur' => '
                let raw = $event.target.value.replace(/\D/g, "");
                $event.target.value = raw === "" ? "" : parseInt(raw).toLocaleString("id-ID");
            ',
                'inputmode' => 'numeric',
            ])
            ->dehydrateStateUsing(fn($state) => (int) preg_replace('/\D/', '', $state ?? '0'))
            ->afterStateHydrated(function ($component, $state) {
                $numeric = (int) preg_replace('/\D/', '', $state ?? '0');
                if ($numeric > 0) {
                    $component->state(number_format($numeric, 0, ',', '.'));
                } else {
                    $component->state('0');
                }
            });
    }
}