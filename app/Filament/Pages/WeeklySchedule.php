<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ScheduleItem;
use App\Models\Team;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Pages\Page;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WeeklySchedule extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Class Schedule';

    protected static ?string $navigationGroup = 'Classroom Tools';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.weekly-schedule';

    public ?string $teamId = null;

    public array $weekdays = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    public function mount(): void
    {
        $user = Auth::user();
        $this->teamId = $user->currentTeam->id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ScheduleItem::query()
                    ->where('team_id', $this->teamId)
            )
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Day')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Start Time')
                    ->time('h:i A')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('End Time')
                    ->time('h:i A')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Class Name')
                    ->searchable(),
                TextColumn::make('location')
                    ->label('Location/Room')
                    ->searchable(),
                ColorColumn::make('color')
                    ->label('Color'),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->getScheduleItemForm())
                    ->visible(fn (ScheduleItem $record): bool => Auth::user()->can('update', $record)
                    ),
                DeleteAction::make()
                    ->visible(fn (ScheduleItem $record): bool => Auth::user()->can('delete', $record)
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->getScheduleItemForm())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['team_id'] = $this->teamId;

                        return $data;
                    })
                    ->visible(fn (): bool => Auth::user()->can('create', ScheduleItem::class)
                    ),
            ]);
    }

    protected function getScheduleItemForm(): array
    {
        $teamName = Auth::user()->currentTeam->name;

        return [
            Select::make('day_of_week')
                ->label('Day of Week')
                ->options(array_combine($this->weekdays, $this->weekdays))
                ->required(),
            TimePicker::make('start_time')
                ->label('Start Time')
                ->seconds(false)
                ->required(),
            TimePicker::make('end_time')
                ->label('End Time')
                ->seconds(false)
                ->required()
                ->after('start_time'),
            TextInput::make('title')
                ->label('Class Name')
                ->default($teamName.' Class')
                ->required()
                ->maxLength(255),
            TextInput::make('location')
                ->label('Location/Room')
                ->maxLength(255),
            ColorPicker::make('color')
                ->label('Color')
                ->default('#4f46e5'),
        ];
    }

    public function getScheduleItemsByDay(string $day): Collection
    {
        return ScheduleItem::where('team_id', $this->teamId)
            ->where('day_of_week', $day)
            ->orderBy('start_time')
            ->get();
    }

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.pages.weekly-schedule'))
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), static::getNavigationBadgeColor())
                ->url(static::getNavigationUrl()),
        ];
    }

    public static function getNavigationUrl(): string
    {
        // Get the current team ID
        $teamId = Auth::user()?->currentTeam?->id;

        if (! $teamId) {
            return '';
        }

        return route('filament.app.pages.weekly-schedule', ['tenant' => $teamId]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show for team members
        return Auth::user()?->currentTeam !== null;
    }
}
