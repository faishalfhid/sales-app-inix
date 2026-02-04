<?php

namespace App\Filament\Widgets;

use App\Models\TrainingClass;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        
        $stats = [];
        
        // Total Training Classes
        $stats[] = Stat::make('Total Training Classes', TrainingClass::count())
            ->description('Total semua kelas pelatihan')
            ->descriptionIcon('heroicon-o-academic-cap')
            ->color('primary');

        // Pending Approval (hanya untuk yang bisa approve)
        if ($user->canApprove()) {
            $pendingCount = TrainingClass::where('status', 'proposed')->count();
            $stats[] = Stat::make('Menunggu Approval', $pendingCount)
                ->description('Kelas yang perlu disetujui')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning');
        }

        // Approved
        $stats[] = Stat::make('Approved', TrainingClass::where('status', 'approved')->count())
            ->description('Kelas yang sudah disetujui')
            ->descriptionIcon('heroicon-o-check-circle')
            ->color('success');

        // Total Revenue (hanya untuk direktur dan GM)
        if ($user->canApprove() || $user->isAdmin()) {
            $totalRevenue = TrainingClass::where('status', 'approved')->sum('total_revenue');
            $stats[] = Stat::make('Total Revenue', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Revenue dari kelas approved')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success');
        }

        return $stats;
    }
}