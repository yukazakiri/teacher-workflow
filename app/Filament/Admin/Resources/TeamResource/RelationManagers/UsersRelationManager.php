<?php

namespace App\Filament\Admin\Resources\TeamResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'editor' => 'Editor',
                        'member' => 'Member',
                    ])
                    ->default('member')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('membership.role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'member')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Add Member')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('User')
                            ->required(),
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'editor' => 'Editor',
                                'member' => 'Member',
                            ])
                            ->default('member')
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit Role')
                    ->modalHeading('Edit Team Member Role')
                    ->form([
                        Forms\Components\Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'editor' => 'Editor',
                                'member' => 'Member',
                            ])
                            ->default('member')
                            ->required(),
                    ]),
                Tables\Actions\DetachAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove Team Member')
                    ->modalDescription('Are you sure you want to remove this member from the team?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove Selected'),
                ]),
            ]);
    }
} 