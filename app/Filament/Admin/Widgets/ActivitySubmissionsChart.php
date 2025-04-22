<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ActivitySubmission;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ActivitySubmissionsChart extends ChartWidget
{
    protected static ?string $heading = 'Activity Submissions';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();
        $months = collect(CarbonPeriod::create($sixMonthsAgo, '1 month', Carbon::now()->endOfMonth()))
            ->map(fn ($date) => $date->format('Y-m'));

        $submissionStats = ActivitySubmission::select(
            DB::raw("to_char(created_at, 'YYYY-MM') as month"),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $labels = $months->map(fn ($month) => Carbon::createFromFormat('Y-m', $month)->format('M Y'))->toArray();
        $submissionData = $months->map(fn ($month) => $submissionStats[$month] ?? 0)->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Submissions',
                    'data' => $submissionData,
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