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
        $user = Auth::user();

        $statuses = [
            'draft'     => ['label' => 'Draft',     'color' => 'rgb(156, 163, 175)'],
            'revision'  => ['label' => 'Revision',  'color' => 'rgb(249, 115, 22)'],
            'proposed'  => ['label' => 'Proposed',  'color' => 'rgb(251, 191, 36)'],
            'approved'  => ['label' => 'Approved',  'color' => 'rgb(34, 197, 94)'],
            'running'   => ['label' => 'Running',   'color' => 'rgb(59, 130, 246)'],
            'completed' => ['label' => 'Completed', 'color' => 'rgb(99, 102, 241)'],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'rgb(239, 68, 68)'],
        ];

        $data   = [];
        $labels = [];
        $colors = [];

        foreach ($statuses as $key => $meta) {
            $query = TrainingClass::query()->where('status', $key);

            // Staff hanya lihat data milik sendiri
            if ($user->isStaff()) {
                $query->where('sales_id', $user->id);
            }

            $count = $query->count();

            if ($count > 0) {
                $data[]   = $count;
                $labels[] = $meta['label'];
                $colors[] = $meta['color'];
            }
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Jumlah Kelas',
                    'data'            => $data,
                    'backgroundColor' => $colors,
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