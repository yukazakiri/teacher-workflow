<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TeamResource\Pages;
use App\Filament\Admin\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Organization';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Team Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('owner', 'name')
                            ->label('Team Owner')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('personal_team')
                            ->label('Personal Team')
                            ->helperText('Is this a personal team for a single user?')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Team Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('personal_team')
                    ->boolean()
                    ->label('Personal'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Members')
                    ->counts('users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('owner')
                    ->relationship('owner', 'name')
                    ->label('Team Owner'),
                Tables\Filters\TernaryFilter::make('personal_team')
                    ->label('Personal Teams Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manageMembers')
                    ->label('Manage Members')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->url(fn (Team $record): string => static::getUrl('members', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
            RelationManagers\TeamInvitationsRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
            RelationManagers\StudentsRelationManager::class,
            RelationManagers\ExamsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
            'members' => Pages\ManageTeamMembers::route('/{record}/members'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
