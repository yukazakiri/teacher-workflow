<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use App\Models\Team;
use App\Models\User;
use Filament\Widgets;
use Filament\PanelProvider;
use Laravel\Fortify\Fortify;
use App\Listeners\SwitchTeam;
use Filament\Pages\Dashboard;
use Filament\Events\TenantSet;
use Filament\Facades\Filament;
use Laravel\Jetstream\Features;
use App\Filament\Pages\EditTeam;
use Laravel\Jetstream\Jetstream;
use App\Filament\Pages\ApiTokens;
use Filament\Navigation\MenuItem;
use App\Filament\Pages\CreateTeam;
use App\Filament\Pages\Gradesheet;
use Filament\Support\Colors\Color;
use App\Filament\Pages\EditProfile;
use Illuminate\Support\Facades\Auth;
use LaraZeus\Boredom\Enums\Variants;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\Type\FalseType;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\ClassesResources;
use App\Filament\Resources\ExamResource;
use Filament\Navigation\NavigationGroup;
use App\Filament\Resources\ClassResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\ActivityResource;
use Illuminate\Session\Middleware\StartSession;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Resources\ClassResourceResource;
use App\Filament\Pages\Dashboard as PagesDashboard;
use App\Filament\Resources\ResourceCategoryResource;
use AssistantEngine\Filament\FilamentAssistantPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use AssistantEngine\Filament\Chat\Pages\AssistantChat;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use TomatoPHP\FilamentSimpleTheme\FilamentSimpleThemePlugin;
use CodeWithDennis\FilamentThemeInspector\FilamentThemeInspectorPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id("app")
            ->path("app")
            ->login()
            ->spa()
            ->brandName("FilaGrade")
            ->sidebarCollapsibleOnDesktop(true)
            // ->topNavigation()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->viteTheme("resources/css/filament/app/theme.css")
            // ->defaultAvatarProvider(\LaraZeus\Boredom\BoringAvatarsProvider::class)
            ->colors([
                "primary" => Color::hex("#c6a0f6"),
                "gray" => Color::hex("#7c7f93"),
                "info" => Color::hex("#7287fd"),
                "danger" => Color::hex("#e78284"),
                "success" => Color::hex("#a6d189"),
                "warning" => Color::hex("#fe640b"),
            ])
            ->renderHook(
                "panels::sidebar.nav.start",
                fn (): string => Blade::render('<livewire:action-shortcuts />'),
            )
            // ->userMenuItems([
            //     MenuItem::make()
            //         ->label('Profile')
            //         ->icon('heroicon-o-user-circle')
            //         ->url(function () use ($panel) {
            //             if ($this->shouldRegisterMenuItem()) {
            //                 // Check if we're in a tenant context
            //                 $tenant = Filament::getTenant();
            //                 if ($tenant) {
            //                     return url(EditProfile::getUrl(['tenant' => $tenant->getKey()]));
            //                 }
            //                 return url(EditProfile::getUrl());
            //             }
            //             return url($panel->getPath());
            //         }),
            // ])
            ->discoverResources(
                in: app_path("Filament/Resources"),
                for: "App\\Filament\\Resources"
            )
            ->discoverPages(
                in: app_path("Filament/Pages"),
                for: "App\\Filament\\Pages"
            )
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                AssistantChat::class,
                EditProfile::class,
                ApiTokens::class,
                \App\Filament\Pages\Gradesheet::class,
                // \App\Filament\Pages\ChatPage::class,

                \App\Filament\Pages\ClassesResources::class,
            ])
            ->renderHook(
                "panels::sidebar.start",
                fn() => view("filament.sidebar.chat-navigation")
            )
            ->plugins([
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled()
                    ->users(fn() => User::pluck("email", "name")->toArray()),
                EasyFooterPlugin::make()->withLoadTime(),
                FilamentAssistantPlugin::make(),
                // FilamentSimpleThemePlugin::make(),
                \LaraZeus\Boredom\BoringAvatarPlugin::make()

                    ->variant(Variants::MARBLE)

                    ->size(60)

                    ->colors([
                        "0A0310",
                        "49007E",
                        "FF005B",
                        "FF7D10",
                        "FFB238",
                    ]),
            ])
            ->discoverWidgets(
                in: app_path("Filament/Widgets"),
                for: "App\\Filament\\Widgets"
            )
            ->navigation(function (
                NavigationBuilder $builder
            ): NavigationBuilder {
                return $builder
                    ->groups([
                        // NavigationGroup::make('Chat History')
                        //     ->items($chatItems),
                    ])
                    ->items([
                        ...Dashboard::getNavigationItems(),
                        // ...AssistantChat::getNavigationItems(),
                        // ...ClassesResources::getNavigationItems(),
                        ...ClassesResources::getNavigationItems(),
                        ...Gradesheet::getNavigationItems(),
                        ...ActivityResource::getNavigationItems(),
                        ...ClassResource::getNavigationItems(),
                        ...ExamResource::getNavigationItems(),
                        ...ResourceCategoryResource::getNavigationItems(),
                        ...StudentResource::getNavigationItems(),
                    ]);
            })
            ->userMenuItems([
                MenuItem::make()
                    ->label("Profile")
                    ->url(
                        fn(): string => EditProfile::getUrl([
                            "tenant" => Auth::user()?->currentTeam->id ?? 1,
                        ])
                    )
                    ->icon("heroicon-o-user-circle"),
            ])
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
                ->tenantMenu(false)
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

        /**
         * Register custom routes for team switching
         */
        Route::middleware([
            "web",
            "auth:sanctum",
            config("jetstream.auth_session"),
            "verified",
        ])->group(function () {
            Route::post("/app/team/switch/{team}", function (Team $team) {
                // This will trigger the TenantSet event which is handled by SwitchTeam listener
                Filament::setTenant($team);

                return redirect()->route("filament.app.pages.dashboard", [
                    "tenant" => $team->id,
                ]);
            })->name("filament.app.team.switch");
        });
    }

    public function shouldRegisterMenuItem(): bool
    {
        $hasVerifiedEmail = Auth::user()?->hasVerifiedEmail();

        return Filament::hasTenancy()
            ? $hasVerifiedEmail && Filament::getTenant()
            : $hasVerifiedEmail;
        return true;
    }
}
