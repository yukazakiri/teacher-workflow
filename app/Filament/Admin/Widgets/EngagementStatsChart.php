<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Attendance;
use App\Models\Channel;
use App\Models\Message;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EngagementStatsChart extends ChartWidget
{
    protected static ?string $heading = 'Engagement Statistics';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        // Attendance statistics
        $attendanceStats = Attendance::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Channel activity
        $twoWeeksAgo = Carbon::now()->subWeeks(2);
        $channelActivity = Channel::select(
            DB::raw("to_char(created_at, 'YYYY-MM-DD') as date"),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $twoWeeksAgo)
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Message statistics
        $messageStats = Message::select(
            DB::raw("to_char(created_at, 'YYYY-MM-DD') as date"),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', $twoWeeksAgo)
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dates = collect(CarbonPeriod::create($twoWeeksAgo, '1 day', Carbon::now()))
            ->map(fn ($date) => $date->format('Y-m-d'));

        return [
            'datasets' => [
                [
                    'label' => 'Channel Activity',
                    'data' => $dates->map(fn ($date) => $channelActivity[$date] ?? 0)->toArray(),
                    'backgroundColor' => '#3B82F6',
                    'borderColor' => '#3B82F6',
                ],
                [
                    'label' => 'Messages',
                    'data' => $dates->map(fn ($date) => $messageStats[$date] ?? 0)->toArray(),
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#10B981',
                ],
            ],
            'labels' => $dates->map(fn ($date) => Carbon::createFromFormat('Y-m-d', $date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
