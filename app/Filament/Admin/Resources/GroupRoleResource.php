<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\GroupRoleResource\Pages;
use App\Filament\Admin\Resources\GroupRoleResource\RelationManagers;
use App\Models\GroupRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GroupRoleResource extends Resource
{
    protected static ?string $model = GroupRole::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    
    protected static ?string $navigationGroup = 'Learning Management';
    
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role Details')
                    ->schema([
                        Forms\Components\Select::make('activity_id')
                            ->relationship('activity', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('activity.title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignments_count')
                    ->label('Assignments')
                    ->counts('assignments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity')
                    ->relationship('activity', 'title'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            // We'll add relation managers later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroupRoles::route('/'),
            'create' => Pages\CreateGroupRole::route('/create'),
            'edit' => Pages\EditGroupRole::route('/{record}/edit'),
        ];
    }
}
