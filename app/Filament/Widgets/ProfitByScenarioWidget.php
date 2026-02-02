<?php

namespace App\Filament\Widgets;

use App\Models\TrainingClass;
use Filament\Widgets\ChartWidget;

class ProfitByScenarioWidget extends ChartWidget
{
    protected static ?string $heading = 'Profit Berdasarkan Skenario';
    
    protected static ?int $sort = 3;
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = TrainingClass::with('scenario')
            ->selectRaw('scenario_id, SUM(net_profit) as total_profit')
            ->groupBy('scenario_id')
            ->get();

        $labels = $data->map(fn ($item) => $item->scenario->name)->toArray();
        $values = $data->map(fn ($item) => $item->total_profit)->toArray();
        
        $colors = [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)',
            'rgb(255, 205, 86)',
            'rgb(75, 192, 192)',
            'rgb(153, 102, 255)',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Net Profit',
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($values)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
