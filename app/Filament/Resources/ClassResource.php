<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassResource\Pages;
use App\Models\ClassResource as ModelsClassResource;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassResource extends Resource
{
    protected static ?string $model = ModelsClassResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Class Resources';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return 'Class Resources';
    }

    public static function getPluralLabel(): string
    {
        return 'Class Resources';
    }

    public static function getModelLabel(): string
    {
        return 'Class Resource';
    }

    public static function form(Form $form): Form
    {
        $team = Filament::getTenant();

        return $form
            ->schema([
                Forms\Components\Section::make('Upload Resource')
                    ->schema([
                        Forms\Components\FileUpload::make('files')
                            ->label('Document File')
                            ->disk('local')
                            // ->directory('class-resources/' . $team->id)
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain'])
                            ->helperText('Upload PDF, Word, Excel, PowerPoint, or image files')
                            ->required()
                            ->maxSize(10240), // 10MB

                        Forms\Components\Select::make('access_level')
                            ->label('Access Level')
                            ->options([
                                'all' => 'All Team Members',
                                'teacher' => 'Teachers Only',
                                'owner' => 'Team Owner Only',
                            ])
                            ->default('all')
                            ->required()
                            ->helperText('Who can access this resource'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('access_level')
                    ->label('Access')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'All Members',
                        'teacher' => 'Teachers Only',
                        'owner' => 'Owner Only',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'all',
                        'warning' => 'teacher',
                        'danger' => 'owner',
                    ]),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('access_level')
                    ->options([
                        'all' => 'All Members',
                        'teacher' => 'Teachers Only',
                        'owner' => 'Owner Only',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClasses::route('/'),
            'create' => Pages\CreateClass::route('/create'),
            'view' => Pages\ViewClass::route('/{record}'),
            'edit' => Pages\EditClass::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $team = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('team_id', $team->id)
            ->with(['creator', 'category', 'media']);
    }
}
