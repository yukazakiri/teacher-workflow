<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Activity;
use Filament\Widgets\ChartWidget;

class ActivityStatsChart extends ChartWidget
{
    protected static ?string $heading = 'Activity Status';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $draft = Activity::where('status', 'draft')->count();
        $published = Activity::where('status', 'published')->count();
        $archived = Activity::where('status', 'archived')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Activities by Status',
                    'data' => [$draft, $published, $archived],
                    'backgroundColor' => ['#94A3B8', '#10B981', '#6B7280'],
                ],
            ],
            'labels' => ['Draft', 'Published', 'Archived'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
