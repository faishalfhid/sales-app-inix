<?php

namespace App\Filament\Widgets;

use App\Models\TrainingClass;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTrainingClassesWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->heading('Kelas Terbaru')
            ->query(
                TrainingClass::query()
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('material')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('scenario.name')
                    ->badge()
                    ->label('Skenario'),
                    
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
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'proposed',
                        'success' => 'approved',
                        'primary' => 'running',
                        'info' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (TrainingClass $record): string => route('filament.admin.resources.training-classes.edit', $record)),
            ]);
    }
}
