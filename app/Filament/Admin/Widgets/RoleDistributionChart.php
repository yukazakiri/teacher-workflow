<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Student;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class RoleDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Role Distribution';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $teachers = User::whereHas('roles', function ($q): void {
            $q->where('name', 'teacher');
        })->count();
        
        $students = Student::count();
        $admins = User::whereHas('roles', function ($q): void {
            $q->where('name', 'admin');
        })->count();

        return [
            'datasets' => [
                [
                    'label' => 'Users by Role',
                    'data' => [$teachers, $students, $admins],
                    'backgroundColor' => ['#3B82F6', '#F59E0B', '#EF4444'],
                ],
            ],
            'labels' => ['Teachers', 'Students', 'Admins'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
} 