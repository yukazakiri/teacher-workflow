<?php

namespace App\Filament\Pages;

use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as PagesDashboard;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\TeamInvitation;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Facades\FilamentIcon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Livewire;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\TeamMembersTableWidget;
use App\Filament\Widgets\PendingInvitationsTableWidget;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use App\Livewire\ChatInterface;

class Dashboard extends PagesDashboard
{
    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Home';
    /**
     * @var view-string
     */
    protected static string $view = 'filament.pages.dashboard';

   

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve('panels::pages.dashboard.navigation-item')
            ?? (Filament::hasTopNavigation() ? 'heroicon-m-home' : 'heroicon-o-home');
    }

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            TeamMembersTableWidget::class,
            PendingInvitationsTableWidget::class,
            ...Filament::getWidgets(),
        ];
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    /**
     * @return int | string | array<string, int | string | null>
     */
    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getTitle(): string | Htmlable
    {
        return static::$title ?? __('filament-panels::pages/dashboard.title');
    }

   

    protected function getViewData(): array
    {
        return [
            'hasTeamFeatures' => Jetstream::hasTeamFeatures(),
        ];
    }
}
