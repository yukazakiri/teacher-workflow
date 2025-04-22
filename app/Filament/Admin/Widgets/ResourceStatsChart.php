<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ClassResource;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ResourceStatsChart extends ChartWidget
{
    protected static ?string $heading = 'Resource Statistics';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();
        $months = collect(CarbonPeriod::create($sixMonthsAgo, '1 month', Carbon::now()->endOfMonth()))
            ->map(fn ($date) => $date->format('Y-m'));

        // Resource activity over time
        $resourceActivity = ClassResource::select(
            DB::raw("to_char(created_at, 'YYYY-MM') as month"),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $labels = $months->map(fn ($month) => Carbon::createFromFormat('Y-m', $month)->format('M Y'))->toArray();
        $activityData = $months->map(fn ($month) => $resourceActivity[$month] ?? 0)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Resources Created',
                    'data' => $activityData,
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#3B82F6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
