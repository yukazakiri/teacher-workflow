<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\FilamentTeamBadgeServiceProvider;
use App\Models\Team;
use App\Observers\TeamObserver;
use App\Livewire\ChatInterface;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(FilamentTeamBadgeServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Team::observe(TeamObserver::class);
        Livewire::component('chat-interface', ChatInterface::class);
    }
}
