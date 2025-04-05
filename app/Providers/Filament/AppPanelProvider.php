<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ApiTokens;
use App\Filament\Pages\AttendanceManager;
use App\Filament\Pages\ClassesResources;
use App\Filament\Pages\CreateTeam;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\EditTeam;
use App\Filament\Pages\Gradesheet;
use App\Filament\Pages\WeeklySchedule;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\AttendanceQrCodeResource;
use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\ExamResource;
use App\Filament\Resources\ResourceCategoryResource;
use App\Filament\Resources\StudentResource;
use App\Listeners\SwitchTeam;
use App\Models\Team;
use App\Models\User;
use AssistantEngine\Filament\Chat\Pages\AssistantChat;
use AssistantEngine\Filament\FilamentAssistantPlugin;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Events\TenantSet;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Jetstream;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use LaraZeus\Boredom\Enums\Variants;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->spa()
            ->brandName('FilaGrade')
            // ->sidebarCollapsibleOnDesktop(true)
            ->sidebarFullyCollapsibleOnDesktop()
            ->emailVerification()
            // ->topNavigation()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->viteTheme('resources/css/filament/app/theme.css')
            ->colors([
                'primary' => Color::hex('#eebebe'),
                'gray' => Color::hex('#949cbb'),
                'info' => Color::hex('#7287fd'),
                'danger' => Color::hex('#f38ba8'),
                'success' => Color::hex('#a6d189'),
                'warning' => Color::hex('#fe640b'),
            ])

            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\\Filament\\Pages'
            )
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                AssistantChat::class,
                EditProfile::class,
                ApiTokens::class,
                \App\Filament\Pages\Gradesheet::class,

                \App\Filament\Pages\ClassesResources::class,
                \App\Filament\Pages\WeeklySchedule::class,
                \App\Filament\Pages\AttendanceManager::class,
                \App\Filament\Pages\Changelogs::class,
            ])
            ->globalSearch(false)

            ->plugins([
                FilamentSocialitePlugin::make()

                    ->providers([

                        Provider::make('google')
                            ->label('Google')
                            ->icon('fab-google-plus-g')
                            ->color(Color::hex('#4285f4'))
                            ->outlined(true)
                            ->stateless(false),
                    ])
                    ->registration(true)
                    ->createUserUsing(function (
                        string $provider,
                        SocialiteUserContract $oauthUser,
                        FilamentSocialitePlugin $plugin
                    ) {
                        // Create the user with basic info first
                        $user = User::create([
                            'name' => $oauthUser->getName(),
                            'email' => $oauthUser->getEmail(),
                            'password' => null, // Important: Password should be nullable
                        ]);

                        // Get avatar URL from OAuth provider
                        $avatarUrl = $oauthUser->getAvatar();

                        if ($avatarUrl) {
                            try {
                                // Download the image to a temporary file
                                $tempFile = tempnam(
                                    sys_get_temp_dir(),
                                    'avatar_'
                                );
                                file_put_contents(
                                    $tempFile,
                                    file_get_contents($avatarUrl)
                                );

                                // Create an UploadedFile instance from the temp file
                                $uploadedFile = new \Illuminate\Http\UploadedFile(
                                    $tempFile,
                                    'avatar.jpg', // Filename
                                    'image/jpeg', // MIME type (adjust if needed)
                                    null,
                                    true // Test mode to avoid moving the file again
                                );

                                // Use Jetstream's method with the proper UploadedFile instance
                                $user->updateProfilePhoto($uploadedFile);

                                // Remove the temporary file
                                @unlink($tempFile);
                            } catch (\Exception $e) {
                                // Log error if avatar download fails
                                report($e);
                            }
                        }

                        return $user; // Return the created user
                    }),
                EasyFooterPlugin::make()
                    ->withLoadTime()
                    ->withSentence(
                        new HtmlString(
                            '<img src="https://static.cdnlogo.com/logos/l/23/filament.svg" style="margin-right:.5rem;" alt="Laravel Logo" width="20" height="20"> Laravel'
                        )
                    )
                    ->withGithub(showLogo: true, showUrl: true)
                    ->withLogo(
                        'https://static.cdnlogo.com/logos/l/23/laravel.svg',
                        'https://laravel.com',
                        'Powered by Laravel'

                    )
                    ->withLinks([
                        [
                            'title' => 'About',
                            'url' => 'https://filagrade.koamishin.org/about',
                        ],
                        [
                            'title' => 'Privacy Policy',
                            'url' => 'https://filagrade.koamishin.org/privacy',
                        ],
                    ])
                    ->withBorder(),
                FilamentAssistantPlugin::make(),
                \LaraZeus\Boredom\BoringAvatarPlugin::make()

                    ->variant(Variants::MARBLE)

                    ->size(60)

                    ->colors([
                        '0A0310',
                        '49007E',
                        'FF005B',
                        'FF7D10',
                        'FFB238',
                    ]),
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\\Filament\\Widgets'
            )
            ->navigation(function (
                NavigationBuilder $builder
            ): NavigationBuilder {
                return $builder
                    ->groups([
                        // Dashboard group
                        NavigationGroup::make()
                            ->label('Dashboard')
                            ->items([
                                ...Dashboard::getNavigationItems(),
                            ]),
                        // Academic group
                        NavigationGroup::make()
                            ->label('Academic')
                            ->items([
                                ...ClassesResources::getNavigationItems(),
                                ...WeeklySchedule::getNavigationItems(),
                                ...Gradesheet::getNavigationItems(),
                                ...ExamResource::getNavigationItems(),
                            ]),
                        // Student Management group
                        NavigationGroup::make()
                            ->label('Student Management')
                            ->items([
                                ...StudentResource::getNavigationItems(),
                                ...AttendanceManager::getNavigationItems(),
                                ...AttendanceResource::getNavigationItems(),
                                ...AttendanceQrCodeResource::getNavigationItems(),
                            ]),
                        // Resources group
                        NavigationGroup::make()
                            ->label('Resources')
                            ->items([
                                ...ActivityResource::getNavigationItems(),
                                ...ResourceCategoryResource::getNavigationItems(),
                            ]),
                        // System group
                        NavigationGroup::make()
                            ->label('System')
                            ->items([
                                ...(\App\Filament\Pages\Changelogs::getNavigationItems()),
                            ]),
                    ])
                    ->items([]);
            })
            ->userMenuItems([
                MenuItem::make()
                    ->label('Profile')
                    ->url(
                        fn(): string => EditProfile::getUrl([
                            'tenant' => Auth::user()?->currentTeam->id ?? 1,
                        ])
                    )
                    ->icon('heroicon-o-user-circle'),
                MenuItem::make()
                    ->label(function () {
                        $githubService = app(
                            \Devonab\FilamentEasyFooter\Services\GitHubService::class
                        );
                        $version = $githubService->getLatestTag();

                        return 'Changelogs (' .
                            (str()->startsWith($version, 'v')
                                ? $version
                                : 'v' . $version) .
                            ')';
                    })
                    ->url(fn() => \App\Filament\Pages\Changelogs::getUrl())
                    ->icon('heroicon-o-document-text'),
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
                    ->label('API Tokens')
                    ->icon('heroicon-o-key')
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
            'web',
            'auth:sanctum',
            config('jetstream.auth_session'),
            'verified',
        ])->group(function () {
            Route::post('/app/team/switch/{team}', function (Team $team) {
                // This will trigger the TenantSet event which is handled by SwitchTeam listener
                Filament::setTenant($team);

                return redirect()->route('filament.app.pages.dashboard', [
                    'tenant' => $team->id,
                ]);
            })->name('filament.app.team.switch');
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
