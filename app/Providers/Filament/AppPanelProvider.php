<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ApiTokens;
use App\Filament\Pages\CreateTeam;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\EditTeam;
use App\Listeners\SwitchTeam;
use App\Models\Team;
use App\Models\User;
use CodeWithDennis\FilamentThemeInspector\FilamentThemeInspectorPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Events\TenantSet;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;
use TomatoPHP\FilamentSimpleTheme\FilamentSimpleThemePlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id("app")
            ->path("app")
            ->login()
            ->sidebarCollapsibleOnDesktop()
              // ->topNavigation()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->viteTheme('resources/css/filament/app/theme.css')
            ->colors([
                'primary' => Color::hex('#c6a0f6'),
                'gray' => Color::hex('#7c7f93'),
                'info' => Color::hex('#7287fd'),
                'danger' => Color::hex('#e78284'),
                'success' => Color::hex('#a6d189'),
                'warning' => Color::hex('#fe640b'),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Profile')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn () => $this->shouldRegisterMenuItem()
                        ? url(EditProfile::getUrl())
                        : url($panel->getPath())),
            ])
            ->discoverResources(
                in: app_path("Filament/Resources"),
                for: "App\\Filament\\Resources"
            )
            ->discoverPages(
                in: app_path("Filament/Pages"),
                for: "App\\Filament\\Pages"
            )
            ->pages([
                // Pages\Dashboard::class,
                // Dashboard::class,
                EditProfile::class,
                ApiTokens::class,
                \App\Filament\Pages\Gradesheet::class,
            ])
            ->plugins([
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled()
                    ->users(fn() => User::pluck("email", "name")->toArray()),
                // FilamentSimpleThemePlugin::make(),
                // FilamentThemeInspectorPlugin::make()
                //     ->toggle(),
            ])
            ->discoverWidgets(
                in: app_path("Filament/Widgets"),
                for: "App\\Filament\\Widgets"
            )
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);

        if (Features::hasApiFeatures()) {
            $panel->userMenuItems([
                MenuItem::make()
                    ->label("API Tokens")
                    ->icon("heroicon-o-key")
                    ->url(
                        fn() => $this->shouldRegisterMenuItem()
                            ? url(ApiTokens::getUrl())
                            : url($panel->getPath())
                    ),
            ]);
        }

        if (Features::hasTeamFeatures()) {
            $panel
                ->tenant(Team::class)
                ->tenantRegistration(CreateTeam::class)
                ->tenantProfile(EditTeam::class)
                // ->tenantMenuItems([
                //     MenuItem::make()
                //         ->label('Settings')
                //         // ->url(fn (): string => Settings::getUrl())
                //         ->icon('heroicon-m-cog-8-tooth'),
                //     // ...
                // ])
                ->userMenuItems([
                    // MenuItem::make()
                    //     ->label(fn () => __('Team Settings'))
                    //     ->icon('heroicon-o-cog-6-tooth')
                    //     ->url(fn () => $this->shouldRegisterMenuItem()
                    //         ? url(EditTeam::getUrl())
                    //         : url($panel->getPath())),
                ]);
        }

        return $panel;
    }

    public function boot()
    {
        /**
         * Disable Fortify routes
         */
        Fortify::$registersRoutes = false;

        /**
         * Disable Jetstream routes
         */
        Jetstream::$registersRoutes = false;

        /**
         * Listen and switch team if tenant was changed
         */
        Event::listen(TenantSet::class, SwitchTeam::class);
    }

    public function shouldRegisterMenuItem(): bool
    {
        // $hasVerifiedEmail = auth()->user()?->hasVerifiedEmail();

        // return Filament::hasTenancy()
        //     ? $hasVerifiedEmail && Filament::getTenant()
        //     : $hasVerifiedEmail;
        return true;
    }
}
