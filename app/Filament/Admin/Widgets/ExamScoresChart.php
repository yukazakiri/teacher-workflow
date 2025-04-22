<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ExamSubmission;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExamScoresChart extends ChartWidget
{
    protected static ?string $heading = 'Exam Scores Over Time';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();
        $months = collect(CarbonPeriod::create($sixMonthsAgo, '1 month', Carbon::now()->endOfMonth()))
            ->map(fn ($date) => $date->format('Y-m'));

        $examScores = ExamSubmission::select(
            DB::raw("to_char(created_at, 'YYYY-MM') as month"),
            DB::raw('AVG(score) as avg_score')
        )
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('month')
            ->pluck('avg_score', 'month')
            ->toArray();

        $labels = $months->map(fn ($month) => Carbon::createFromFormat('Y-m', $month)->format('M Y'))->toArray();
        $scoreData = $months->map(fn ($month) => round($examScores[$month] ?? 0, 1))->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Average Score',
                    'data' => $scoreData,
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