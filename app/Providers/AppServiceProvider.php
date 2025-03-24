<?php

namespace App\Providers;

use App\Models\Team;
use Livewire\Livewire;
use Prism\Prism\Prism;
use App\Livewire\ChatInterface;
use App\Observers\TeamObserver;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\PrismServer;
use Prism\Prism\Text\PendingRequest;
use App\Providers\EventServiceProvider;
use Illuminate\Support\ServiceProvider;
use App\Providers\FilamentTeamBadgeServiceProvider;

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
        $this->configurePrisms();
    }
    private function configurePrisms(): void
    {
        // This is example of how to register a Prism.
        PrismServer::register(
            'Gemini 1.5 Flash',
            fn (): PendingRequest => Prism::text()->using(Provider::Gemini, 'gemini-1.5-flash')
                ->withSystemPrompt(view('prompts.system')->render())
                ->withMaxTokens(1000)
        );

        PrismServer::register(
            'Gemini 2.0 Flash',
            fn (): PendingRequest => Prism::text()->using(Provider::Gemini, 'gemini-2.0-flash')
                ->withSystemPrompt(view('prompts.system')->render())
                ->withMaxTokens(1500)
                
        );

        PrismServer::register(
            'GPT-4o Mini',
            fn (): PendingRequest => Prism::text()->using(Provider::OpenAI, 'gpt-4o-mini')
                ->withSystemPrompt(view('prompts.system')->render())
                 ->withMaxTokens(2500)
        );
    }
}
