<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\ActivityStatsChart;
use App\Filament\Admin\Widgets\ExamScoresChart;
use App\Filament\Admin\Widgets\ExamStatsChart;
use App\Filament\Admin\Widgets\ResourceStatsChart;
use App\Filament\Admin\Widgets\RoleDistributionChart;
use App\Filament\Admin\Widgets\StatsOverview;
use App\Filament\Admin\Widgets\UserStatsChart;
use Filament\Pages\Page;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.admin.pages.analytics-dashboard';
    
    protected static ?string $navigationLabel = 'Analytics Dashboard';
    
    protected static ?string $navigationGroup = 'Organization';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $title = 'Analytics Dashboard';
    
    protected static ?string $slug = 'analytics';
    
    public function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }
    
    public function getFooterWidgets(): array
    {
        return [
            UserStatsChart::class,
            RoleDistributionChart::class,
            ActivityStatsChart::class,
            ExamStatsChart::class,
            ExamScoresChart::class,
            ResourceStatsChart::class,
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
} 