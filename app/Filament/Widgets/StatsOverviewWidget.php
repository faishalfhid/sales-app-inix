<?php

namespace App\Filament\Widgets;

use App\Models\TrainingClass;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;


class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        $query = TrainingClass::query();

        if ($user && $user->role === 'staff') {
            $query->where('sales_id', $user->id);
        }

        // Get data
        $totalClasses = (clone $query)->count();
        $activeClasses = (clone $query)
            ->whereIn('status', ['approved', 'running'])
            ->count();
        $totalRevenue = (clone $query)->sum('total_revenue');
        $totalProfit = (clone $query)->sum('net_profit');
        $avgProfitMargin = (clone $query)
            ->where('net_profit_margin', '>', 0)
            ->avg('net_profit_margin');


        // Calculate trends (last 30 days vs previous 30 days)
        $currentMonthClasses = (clone $query)
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->count();

        $previousMonthClasses = (clone $query)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        $classTrend = $previousMonthClasses > 0
            ? (($currentMonthClasses - $previousMonthClasses) / $previousMonthClasses) * 100
            : 0;

        $currentMonthRevenue = (clone $query)
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->sum('total_revenue');

        $previousMonthRevenue = (clone $query)
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->sum('total_revenue');

        $revenueTrend = $previousMonthRevenue > 0
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100
            : 0;

        // Pending approval count
        $pendingCount = (clone $query)
            ->where('status', 'proposed')
            ->count();

        $stats = [];

        // Stat 1: Total Kelas
        $stats[] = Stat::make('Total Kelas', $totalClasses)
            ->description($classTrend >= 0 ? number_format($classTrend, 1) . '% increase' : number_format(abs($classTrend), 1) . '% decrease')
            ->descriptionIcon($classTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($this->getClassTrendData())
            ->color($classTrend >= 0 ? 'success' : 'danger');

        // Stat 2: Menunggu Konfirmasi (hanya untuk Direktur & GM)
        if ($user && $user->canApprove()) {
            $stats[] = Stat::make('Menunggu Konfirmasi', $pendingCount)
                ->description('Kelas yang perlu disetujui')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.training-classes.index', [
                    'tableFilters' => ['status' => ['value' => 'proposed']]
                ]))
                ->extraAttributes([
                    'class' => $pendingCount > 0 ? 'cursor-pointer hover:shadow-lg transition-shadow' : '',
                ]);
        }

        // Stat 3: Kelas Aktif
        $stats[] = Stat::make('Kelas Aktif', $activeClasses)
            ->description('Approved & Running')
            ->descriptionIcon('heroicon-m-academic-cap')
            ->color('info');

        // Stat 4: Total Revenue
        $stats[] = Stat::make('Total Revenue', 'Rp ' . Number::format($totalRevenue, locale: 'id'))
            ->description($revenueTrend >= 0 ? number_format($revenueTrend, 1) . '% increase' : number_format(abs($revenueTrend), 1) . '% decrease')
            ->descriptionIcon($revenueTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->chart($this->getRevenueTrendData())
            ->color($revenueTrend >= 0 ? 'success' : 'danger');

        // Stat 5: Total Net Profit (hanya untuk user yang bisa approve atau admin)
        if ($user && ($user->canApprove() || $user->isAdmin())) {
            $stats[] = Stat::make('Total Net Profit', 'Rp ' . Number::format($totalProfit, locale: 'id'))
                ->description('Dari semua kelas')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($totalProfit >= 0 ? 'success' : 'danger');
        }

        // Stat 6: Avg Profit Margin (hanya untuk user yang bisa approve atau admin)
        if ($user && ($user->canApprove() || $user->isAdmin())) {
            $stats[] = Stat::make('Avg Profit Margin', number_format($avgProfitMargin ?? 0, 2) . '%')
                ->description('Rata-rata margin keuntungan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning');
        }

        // Stat 7: Total Peserta
        $stats[] = Stat::make('Total Peserta', Number::format(TrainingClass::sum('participant_count'), locale: 'id'))
            ->description('Dari semua kelas')
            ->descriptionIcon('heroicon-m-user-group')
            ->color('primary');

        return $stats;
    }

    private function getClassTrendData(): array
    {
        $query = TrainingClass::query();

        $user = auth()->user();


        if ($user && $user->role === 'staff') {
            $query->where('sales_id', $user->id);
        }

        return $query
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }


    private function getRevenueTrendData(): array
    {
        $query = TrainingClass::query();

        $user = auth()->user();


        if ($user && $user->role === 'staff') {
            $query->where('sales_id', $user->id);
        }

        return $query
            ->selectRaw('DATE(created_at) as date, SUM(total_revenue) as revenue')
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue')
            ->toArray();
    }

}