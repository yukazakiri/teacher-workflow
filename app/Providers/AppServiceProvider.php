<?php

namespace App\Providers;

use App\Models\Team;
use Livewire\Livewire;
use Prism\Prism\Prism;
use App\Livewire\ChatInterface;
use App\Observers\TeamObserver;
use Prism\Prism\Enums\Provider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Prism\Prism\Facades\PrismServer;
use Prism\Prism\Text\PendingRequest;
use Illuminate\Support\Facades\Blade;
use App\Providers\EventServiceProvider;
use Illuminate\Support\ServiceProvider;
use App\Providers\FilamentTeamBadgeServiceProvider;
use App\Tools\DataAccessTool;
use Prism\Prism\Facades\Tool;

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
        Livewire::component("chat-interface", ChatInterface::class);
        $this->configurePrisms();

        // Register the SafeQrCode component
        Blade::component(
            "safe-qr-code",
            \App\View\Components\SafeQrCode::class
        );
        $this->configureUrl();
        $this->configureVite();
        // We're going to use our helper directly instead of directives
        // to avoid any closure/stringable issues
        //
        // Tool::register(DataAccessTool::class);
    }
    private function configurePrisms(): void
    {
        // This is example of how to register a Prism.
        PrismServer::register(
            "Gemini 1.5 Flash",
            fn(): PendingRequest => Prism::text()
                ->using(Provider::Gemini, "gemini-1.5-flash")
                ->withSystemPrompt(view("prompts.system")->render())
                ->withMaxTokens(1000)
        );

        PrismServer::register(
            "Gemini 2.0 Flash",
            fn(): PendingRequest => Prism::text()
                ->using(Provider::Gemini, "gemini-2.0-flash")
                ->withSystemPrompt(view("prompts.system")->render())
                ->withMaxTokens(1500)
        );

        PrismServer::register(
            "GPT-4o Mini",
            fn(): PendingRequest => Prism::text()
                ->using(Provider::OpenAI, "gpt-4o-mini")
                ->withSystemPrompt(view("prompts.system")->render())
                ->withMaxTokens(2500)
        );
    }
    private function configureUrl(): void
    {
        URL::forceHttps(App::isProduction());
    }
    private function configureVite(): void
    {
        Vite::useAggressivePrefetching();
    }
}
