<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\StatisticsDashboard;
use App\Filament\Admin\Widgets\StatsOverview;
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
            StatisticsDashboard::class,
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
} 