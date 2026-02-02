<?php

namespace App\Filament\Widgets;

use App\Models\TrainingClass;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 6;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top 10 Customers')
            ->query(
                TrainingClass::query()
                    ->selectRaw('customer, COUNT(*) as total_classes, SUM(total_revenue) as total_revenue, SUM(net_profit) as total_profit, SUM(participant_count) as total_participants')
                    ->whereNotNull('customer')
                    ->groupBy('customer')
                    ->orderByDesc('total_revenue')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer')
                    ->label('Customer')
                    ->searchable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('total_classes')
                    ->label('Total Kelas')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('total_participants')
                    ->label('Total Peserta')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('total_profit')
                    ->label('Total Profit')
                    ->money('IDR')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),
            ]);
    }
}
