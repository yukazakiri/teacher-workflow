<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\ScheduleItem;
use App\Models\Student;
use App\Policies\ActivityPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\ScheduleItemPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Activity::class => ActivityPolicy::class,
        Student::class => StudentPolicy::class,
        Attendance::class => AttendancePolicy::class,
        ScheduleItem::class => ScheduleItemPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
