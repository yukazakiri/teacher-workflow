<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Attendance;
use App\Models\ClassResource;
use App\Models\Exam;
use App\Models\ExamSubmission;
use App\Models\Message;
use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            // User Stats
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Active Users (30d)', User::whereHas('sessions', function ($query): void {
                $query->where('last_activity', '>=', Carbon::now()->subDays(30)->timestamp);
            })->count())
                ->description('Users active in last 30 days')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('New Users Today', User::where('created_at', '>=', Carbon::today())->count())
                ->description('New registrations today')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),

            // Activity Stats
            Stat::make('Total Activities', Activity::count())
                ->description('All learning activities')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Completed Submissions', ActivitySubmission::where('status', 'completed')->count())
                ->description('Successfully completed activities')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Pending Submissions', ActivitySubmission::where('status', 'pending')->count())
                ->description('Activities in progress')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            // Exam Stats
            Stat::make('Total Exams', Exam::count())
                ->description('All examinations')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Average Score', ExamSubmission::avg('score') ? round(ExamSubmission::avg('score'), 1) : 0)
                ->description('Average exam performance')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('success'),

            Stat::make('Highest Score', ExamSubmission::max('score') ?? 0)
                ->description('Best exam performance')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),

            // Resource Stats
            Stat::make('Total Resources', ClassResource::count())
                ->description('All learning resources')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Featured Resources', ClassResource::where('is_pinned', true)->count())
                ->description('Pinned resources')
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),

            Stat::make('Recent Resources', ClassResource::where('created_at', '>=', Carbon::now()->subDays(7))->count())
                ->description('Added in last 7 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
