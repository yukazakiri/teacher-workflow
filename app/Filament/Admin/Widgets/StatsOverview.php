<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Activity;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Total registered users')
                ->descriptionIcon('heroicon-m-user')
                ->color('primary'),

            Stat::make('Total Teams', Team::count())
                ->description('Active teams')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Students', Student::count())
                ->description('Registered students')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning'),

            Stat::make('Activities', Activity::count())
                ->description(Activity::where('status', 'published')->count().' published')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('danger'),

            Stat::make('Exams', Exam::count())
                ->description('Total exams')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),
        ];
    }
}
