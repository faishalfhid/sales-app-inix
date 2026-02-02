<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
class Dashboard extends BaseDashboard
{
    use HasFiltersForm;
    
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Dari Tanggal')
                            ->default(now()->subDays(30)),
                            
                        DatePicker::make('endDate')
                            ->label('Sampai Tanggal')
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }


    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\RevenueChartWidget::class,
            \App\Filament\Widgets\ProfitByScenarioWidget::class,
            \App\Filament\Widgets\ClassStatusChartWidget::class,
            \App\Filament\Widgets\LatestTrainingClassesWidget::class,
            // \App\Filament\Widgets\TopCustomersWidget::class,
        ];
    }
}
