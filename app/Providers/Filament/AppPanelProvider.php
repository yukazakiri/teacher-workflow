<?php

namespace App\Providers\Filament;

use Filament\Panel;
use App\Models\Team;
use App\Models\User;
use Filament\Widgets;
use Filament\PanelProvider;
use App\Livewire\SelectRole;
use Laravel\Fortify\Fortify;
use App\Listeners\SwitchTeam;
use Filament\Pages\Dashboard;
use Filament\Events\TenantSet;
use Filament\Facades\Filament;
use Laravel\Jetstream\Features;
use App\Filament\Pages\EditTeam;
use App\Filament\Pages\Messages;
use Laravel\Jetstream\Jetstream;
use App\Filament\Pages\ApiTokens;
use Filament\Navigation\MenuItem;
use App\Filament\Pages\CreateTeam;
use App\Filament\Pages\Gradesheet;
use Filament\Support\Colors\Color;
use Illuminate\Support\HtmlString;
use App\Filament\Pages\EditProfile;
use Illuminate\Support\Facades\Auth;
use LaraZeus\Boredom\Enums\Variants;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use App\Filament\Pages\WeeklySchedule;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\ClassesResources;
use App\Filament\Resources\ExamResource;
use Filament\Navigation\NavigationGroup;
use App\Filament\Pages\AttendanceManager;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\ActivityResource;
use App\Filament\Resources\AttendanceResource;
use Illuminate\Session\Middleware\StartSession;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Illuminate\Cookie\Middleware\EncryptCookies;
use DutchCodingCompany\FilamentSocialite\Provider;
use App\Filament\Resources\AttendanceQrCodeResource;
use App\Filament\Resources\ResourceCategoryResource;
use AssistantEngine\Filament\FilamentAssistantPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use AssistantEngine\Filament\Chat\Pages\AssistantChat;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\WorkOS\Http\Requests\AuthKitLogoutRequest;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
            // ->sidebarCollapsibleOnDesktop(true)
            ->sidebarFullyCollapsibleOnDesktop()
            // ->emailVerification()
            // ->topNavigation()
            ->passwordReset()
            ->emailVerification()
            ->databaseNotifications()
            ->homeUrl(function () {
                $user = Auth::user();
                $team = $user?->currentTeam;

                if (!$team) {
                    return "/app";
                }

                $membership = DB::table("team_user")
                    ->where("team_id", $team->id)
                    ->where("user_id", $user->id)
                    ->first();

                $role = $membership->role ?? null;

                if ($role === "student") {
                    return route("filament.app.pages.student-dashboard", [
                        "tenant" => $team->id,
                    ]);
                } elseif ($role === "parent") {
                    return route("filament.app.pages.parent-dashboard", [
                        "tenant" => $team->id,
                    ]);
                }

                // Default to the main dashboard for teachers or undefined roles
                return route("filament.app.pages.dashboard", [
                    "tenant" => $team->id,
                ]);
            })
            ->viteTheme("resources/css/filament/app/theme.css")
            ->colors([
                "primary" => Color::hex("#eebebe"),
                "gray" => Color::hex("#949cbb"),
                "info" => Color::hex("#7287fd"),
                "danger" => Color::hex("#f38ba8"),
                "success" => Color::hex("#a6d189"),
                "warning" => Color::hex("#fe640b"),
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
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\StudentDashboard::class,
                \App\Filament\Pages\ParentDashboard::class,
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
                        Provider::make("google")
                            ->label("Google")
                            ->icon("fab-google-plus-g")
                            ->color(Color::hex("#4285f4"))
                            ->outlined(true)
                            ->stateless(false),
                        Provider::make("facebook")
                            ->label("Facebook")
                            ->icon("fab-facebook")
                            ->color(Color::hex("#4267B2"))
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
                            "name" => $oauthUser->getName(),
                            "email" => $oauthUser->getEmail(),
                            "password" => null, // Important: Password should be nullable
                        ]);

                        // Get avatar URL from OAuth provider
                        $avatarUrl = $oauthUser->getAvatar();

                        if ($avatarUrl) {
                            try {
                                // Download the image to a temporary file
                                $tempFile = tempnam(
                                    sys_get_temp_dir(),
                                    "avatar_"
                                );
                                file_put_contents(
                                    $tempFile,
                                    file_get_contents($avatarUrl)
                                );

                                // Create an UploadedFile instance from the temp file
                                $uploadedFile = new \Illuminate\Http\UploadedFile(
                                    $tempFile,
                                    "avatar.jpg", // Filename
                                    "image/jpeg", // MIME type (adjust if needed)
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

                // FilamentDeveloperLoginsPlugin::make()
                //     ->enabled()
                //     ->users([
                //         "teacher" => "test@example.com",
                //         "User" => "sdavis@student.edu",
                //         "parent" => "your.email+fakedata38141@gmail.com",
                //     ]),

                FilamentAssistantPlugin::make(),
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
                $user = Auth::user();
                $team = $user?->currentTeam;
                $role = null;

                if ($team) {
                    $membership = DB::table("team_user")
                        ->where("team_id", $team->id)
                        ->where("user_id", $user->id)
                        ->first();

                    $role = $membership->role ?? null;
                }

                // Start building navigation
                $navigationBuilder = $builder->groups([]);

                // For teachers or undefined roles, show the default navigation
                if (!$role || $role === "teacher" || $role === "pending") {
                    $navigationBuilder = $builder->groups([
                        // Dashboard group
                        NavigationGroup::make()
                            ->label("Dashboard")
                            ->items([
                                ...Dashboard::getNavigationItems(),
                                ...Messages::getNavigationItems(),
                            ]),
                        // Academic group
                        NavigationGroup::make()
                            ->label("Academic")
                            ->items([
                                ...ClassesResources::getNavigationItems(),
                                ...WeeklySchedule::getNavigationItems(),
                                ...Gradesheet::getNavigationItems(),
                                ...ExamResource::getNavigationItems(),
                            ]),
                        // Student Management group
                        NavigationGroup::make()
                            ->label("Student Management")
                            ->items([
                                ...StudentResource::getNavigationItems(),
                                ...AttendanceManager::getNavigationItems(),
                                ...AttendanceResource::getNavigationItems(),
                                ...AttendanceQrCodeResource::getNavigationItems(),
                            ]),
                        // Resources group
                        NavigationGroup::make()
                            ->label("Resources")
                            ->items([
                                ...ActivityResource::getNavigationItems(),
                                ...ResourceCategoryResource::getNavigationItems(),
                            ]),
                        // System group
                        NavigationGroup::make()
                            ->label("System")
                            ->items([
                                ...\App\Filament\Pages\Changelogs::getNavigationItems(),
                            ]),
                    ]);
                }
                // For students, show student navigation
                elseif ($role === "student") {
                    $navigationBuilder = $builder->groups([
                        NavigationGroup::make()
                            ->label("Student Dashboard")
                            ->items([
                                ...\App\Filament\Pages\StudentDashboard::getNavigationItems(),
                                ...Messages::getNavigationItems(),
                            ]),
                        NavigationGroup::make()
                            ->label("Academic")
                            ->items([
                                ...WeeklySchedule::getNavigationItems(),
                                ...ClassesResources::getNavigationItems(),
                                ...AttendanceResource::getNavigationItems(),
                                ...ActivityResource::getNavigationItems(),
                            ]),
                    ]);
                }
                // For parents, show parent navigation
                elseif ($role === "parent") {
                    $navigationBuilder = $builder->groups([
                        NavigationGroup::make()
                            ->label("Parent Dashboard")
                            ->items([
                                ...\App\Filament\Pages\ParentDashboard::getNavigationItems(),
                            ]),
                        NavigationGroup::make()
                            ->label("Academic")
                            ->items([...Gradesheet::getNavigationItems()]),
                    ]);
                }

                return $navigationBuilder->items([]);
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
                MenuItem::make()
                    ->label(function () {
                        $githubService = app(
                            \Devonab\FilamentEasyFooter\Services\GitHubService::class
                        );
                        $version = $githubService->getLatestTag();

                        return "Changelogs (" .
                            (str()->startsWith($version, "v")
                                ? $version
                                : "v" . $version) .
                            ")";
                    })
                    ->url(
                        fn() => \App\Filament\Pages\Changelogs::getUrl([
                            "tenant" => Auth::user()?->currentTeam->id ?? 1,
                        ])
                    )
                    ->icon("heroicon-o-document-text"),
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
            ->authMiddleware([Authenticate::class])
            ->livewireComponents([
                // Register other Livewire components
                SelectRole::class,
            ]);

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
        ])->group(function (): void {
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
