<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceQrCodeResource\Pages;
use App\Models\AttendanceQrCode;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceQrCodeResource extends Resource
{
    protected static ?string $model = AttendanceQrCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    
    protected static ?string $navigationGroup = 'Class Management';

    protected static ?int $navigationSort = 6;
    
    protected static ?string $recordTitleAttribute = 'description';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        
        if (!$team) {
            return false;
        }
        
        return $team->userIsOwner($user);
    }
    
    /**
     * Determine if this resource's navigation item should be displayed.
     * Only show it for team owners.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
    
    /**
     * Get the navigation items for this resource.
     * Only team owners should see these navigation items.
     * 
     * @return array
     */
    public static function getNavigationItems(): array
    {
        if (!static::canAccess()) {
            return [];
        }
        
        return parent::getNavigationItems();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('team_id')
                    ->label('Class')
                    ->relationship('team', 'name')
                    ->default(Auth::user()->currentTeam->id)
                    ->required()
                    ->searchable()
                    ->preload(),
                
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->default(now()),
                
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires At')
                    ->required()
                    ->default(function () {
                        return now()->addMinutes(30);
                    }),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->placeholder('e.g., Morning Class Attendance')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Class')
                    ->sortable()
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn (Carbon $state) => $state->isPast() ? 'danger' : 'success'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('code')
                    ->label('QR Code')
                    ->copyable()
                    ->limit(10),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('team_id')
                    ->label('Class')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\Filter::make('is_active')
                    ->label('Active')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('extend')
                    ->label('Extend Expiry')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('minutes')
                            ->label('Extend By')
                            ->options([
                                15 => '15 minutes',
                                30 => '30 minutes',
                                60 => '1 hour',
                                120 => '2 hours',
                            ])
                            ->required(),
                    ])
                    ->action(function (AttendanceQrCode $record, array $data): void {
                        $record->extendExpiry($data['minutes']);
                    }),
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->action(function (AttendanceQrCode $record): void {
                        $record->deactivate();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (AttendanceQrCode $record): bool => $record->is_active),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('deactivateBulk')
                        ->label('Deactivate All')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->action(function (array $records): void {
                            foreach ($records as $record) {
                                $record->deactivate();
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceQrCodes::route('/'),
            'create' => Pages\CreateAttendanceQrCode::route('/create'),
            'edit' => Pages\EditAttendanceQrCode::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('team', function (Builder $query) {
                $query->where('id', Auth::user()->currentTeam->id);
            });
    }
}
