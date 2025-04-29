<?php

namespace App\Providers;

use App\Events\MessageSent;
use App\Listeners\CreateStudentFromTeamMember;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamMemberAdded;
use App\Models\Exam; // Add Exam model import
use App\Observers\ExamObserver; // Add ExamObserver import

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TeamMemberAdded::class => [
            CreateStudentFromTeamMember::class,
        ],
        MessageSent::class => [
            // You can add custom listeners here if needed
        ],
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
        Exam::observe(ExamObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
