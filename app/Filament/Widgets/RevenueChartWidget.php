<?php

namespace App\Filament\Widgets;

use App\Models\TrainingClass;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Revenue & Profit (30 Hari Terakhir)';
    
    protected static ?int $sort = 2;
    
    protected static ?string $maxHeight = '300px';
    
    public ?string $filter = '30';

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        
        $revenueData = Trend::model(TrainingClass::class)
            ->between(
                start: now()->subDays($activeFilter),
                end: now(),
            )
            ->perDay()
            ->sum('total_revenue');
            
        $profitData = Trend::model(TrainingClass::class)
            ->between(
                start: now()->subDays($activeFilter),
                end: now(),
            )
            ->perDay()
            ->sum('net_profit');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenueData->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Net Profit',
                    'data' => $profitData->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
            ],
            'labels' => $revenueData->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getFilters(): ?array
    {
        return [
            7 => '7 Hari',
            30 => '30 Hari',
            60 => '60 Hari',
            90 => '90 Hari',
        ];
    }
}
