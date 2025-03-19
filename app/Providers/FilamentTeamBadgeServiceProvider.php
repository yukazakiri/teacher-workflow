<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Illuminate\Support\ServiceProvider;

class FilamentTeamBadgeServiceProvider extends ServiceProvider
{
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
        // Register the render hook for the team badge
        FilamentView::registerRenderHook(
            'panels::topbar.start',
            fn (): string => view('components.current-team-badge')->render(),
        );
    }
} 