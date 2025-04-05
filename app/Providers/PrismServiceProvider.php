<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prism\Prism\PrismManager;

class PrismServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // We don't need to register anything as the package already registers the services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Just make sure the alias exists
        if (! $this->app->bound('prism')) {
            $this->app->alias(PrismManager::class, 'prism');
        }
    }
}
