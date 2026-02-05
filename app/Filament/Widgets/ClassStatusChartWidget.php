<?php

namespace App\Filament\Widgets;

use App\Models\TrainingClass;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;


class ClassStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Status Kelas';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $userId = Auth::id();

        $statuses = [
            'draft' => 'Draft',
            'proposed' => 'Proposed',
            'approved' => 'Approved',
            'running' => 'Running',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $data = [];
        $labels = [];

        foreach ($statuses as $key => $label) {
            $count = TrainingClass::query()
                ->where('sales_id', $userId)
                ->where('status', $key)
                ->count();
            if ($count > 0) {
                $data[] = $count;
                $labels[] = $label;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Kelas',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgb(156, 163, 175)', // draft - gray
                        'rgb(251, 191, 36)',  // proposed - yellow
                        'rgb(34, 197, 94)',   // approved - green
                        'rgb(59, 130, 246)',  // running - blue
                        'rgb(99, 102, 241)',  // completed - indigo
                        'rgb(239, 68, 68)',   // cancelled - red
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
