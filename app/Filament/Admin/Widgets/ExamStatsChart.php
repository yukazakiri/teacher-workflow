<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ExamSubmission;
use Filament\Widgets\ChartWidget;

class ExamStatsChart extends ChartWidget
{
    protected static ?string $heading = 'Exam Results';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $passingGrade = 60;
        $passed = ExamSubmission::where('score', '>=', $passingGrade)->count();
        $failed = ExamSubmission::where('score', '<', $passingGrade)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Exam Results',
                    'data' => [$passed, $failed],
                    'backgroundColor' => ['#10B981', '#EF4444'],
                ],
            ],
            'labels' => ['Passed', 'Failed'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
