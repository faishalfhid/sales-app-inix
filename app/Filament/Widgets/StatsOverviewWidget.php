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
        // Get data
        $totalClasses = TrainingClass::count();
        $activeClasses = TrainingClass::whereIn('status', ['approved', 'running'])->count();
        $totalRevenue = TrainingClass::sum('total_revenue');
        $totalProfit = TrainingClass::sum('net_profit');
        $avgProfitMargin = TrainingClass::where('net_profit_margin', '>', 0)->avg('net_profit_margin');
        
        // Calculate trends (last 30 days vs previous 30 days)
        $currentMonthClasses = TrainingClass::whereBetween('created_at', [now()->subDays(30), now()])->count();
        $previousMonthClasses = TrainingClass::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();
        $classTrend = $previousMonthClasses > 0 
            ? (($currentMonthClasses - $previousMonthClasses) / $previousMonthClasses) * 100 
            : 0;
        
        $currentMonthRevenue = TrainingClass::whereBetween('created_at', [now()->subDays(30), now()])->sum('total_revenue');
        $previousMonthRevenue = TrainingClass::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->sum('total_revenue');
        $revenueTrend = $previousMonthRevenue > 0 
            ? (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100 
            : 0;

        return [
            Stat::make('Total Kelas', $totalClasses)
                ->description($classTrend >= 0 ? number_format($classTrend, 1) . '% increase' : number_format(abs($classTrend), 1) . '% decrease')
                ->descriptionIcon($classTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getClassTrendData())
                ->color($classTrend >= 0 ? 'success' : 'danger'),
                
            Stat::make('Kelas Aktif', $activeClasses)
                ->description('Approved & Running')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),
                
            Stat::make('Total Revenue', 'Rp ' . Number::format($totalRevenue, locale: 'id'))
                ->description($revenueTrend >= 0 ? number_format($revenueTrend, 1) . '% increase' : number_format(abs($revenueTrend), 1) . '% decrease')
                ->descriptionIcon($revenueTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getRevenueTrendData())
                ->color($revenueTrend >= 0 ? 'success' : 'danger'),
                
            Stat::make('Total Net Profit', 'Rp ' . Number::format($totalProfit, locale: 'id'))
                ->description('Dari semua kelas')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($totalProfit >= 0 ? 'success' : 'danger'),
                
            Stat::make('Avg Profit Margin', number_format($avgProfitMargin ?? 0, 2) . '%')
                ->description('Rata-rata margin keuntungan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
                
            Stat::make('Total Peserta', TrainingClass::sum('participant_count'))
                ->description('Dari semua kelas')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
    
    private function getClassTrendData(): array
    {
        return TrainingClass::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();
    }
    
    private function getRevenueTrendData(): array
    {
        return TrainingClass::selectRaw('DATE(created_at) as date, SUM(total_revenue) as revenue')
            ->whereBetween('created_at', [now()->subDays(7), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('revenue')
            ->toArray();
    }
}
