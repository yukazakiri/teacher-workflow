<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\ClassResource;
use App\Models\ScheduleItem;
use App\Models\Student;
use App\Models\Team;
use App\Policies\ActivityPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\ClassResourcePolicy;
use App\Policies\ScheduleItemPolicy;
use App\Policies\StudentPolicy;
use App\Policies\TeamPolicy;
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
        ClassResource::class => ClassResourcePolicy::class,
        Team::class => TeamPolicy::class,
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

        // Grant super admin access to specific email
        Gate::before(function ($user, $ability) {
            if ($user->email === 'marianolukkanit17@gmail.com') {
                return true;
            }
            if ($user->email === 'test@example.com') {
                return true;
            }
        });
    }
}
