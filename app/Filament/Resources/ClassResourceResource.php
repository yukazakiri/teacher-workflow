<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassResourceResource\Pages;
use App\Models\ClassResource;
use App\Models\ResourceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ClassResourceResource extends Resource
{
    protected static ?string $model = ClassResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Class Management';
    
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
                Forms\Components\Section::make('Resource Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(function () use ($team) {
                                return ResourceCategory::where('team_id', $team->id)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->rows(3),
                                Forms\Components\ColorPicker::make('color')
                                    ->default('#4f46e5'),
                                Forms\Components\Select::make('icon')
                                    ->options([
                                        'heroicon-o-document' => 'Document',
                                        'heroicon-o-document-text' => 'Document Text',
                                        'heroicon-o-book-open' => 'Book Open',
                                        'heroicon-o-academic-cap' => 'Academic Cap',
                                        'heroicon-o-calculator' => 'Calculator',
                                        'heroicon-o-beaker' => 'Beaker',
                                        'heroicon-o-chart-bar' => 'Chart Bar',
                                        'heroicon-o-clipboard' => 'Clipboard',
                                        'heroicon-o-globe-alt' => 'Globe',
                                        'heroicon-o-light-bulb' => 'Light Bulb',
                                        'heroicon-o-pencil' => 'Pencil',
                                        'heroicon-o-puzzle-piece' => 'Puzzle Piece',
                                        'heroicon-o-video-camera' => 'Video Camera',
                                    ])
                                    ->default('heroicon-o-document'),
                            ])
                            ->createOptionUsing(function (array $data) use ($team) {
                                return ResourceCategory::create([
                                    'team_id' => $team->id,
                                    'name' => $data['name'],
                                    'description' => $data['description'] ?? null,
                                    'color' => $data['color'] ?? '#4f46e5',
                                    'icon' => $data['icon'] ?? 'heroicon-o-document',
                                ]);
                            }),
                            
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpan('full'),
                            
                        Forms\Components\Select::make('access_level')
                            ->options([
                                'all' => 'Everyone in class',
                                'teacher' => 'Teachers only',
                                'owner' => 'Class owner only',
                            ])
                            ->default('all')
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Files')
                    ->schema([
                        FileUpload::make('files')
                            ->multiple()
                            ->maxFiles(10)
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain', 'application/zip', 'application/x-rar-compressed'])
                            ->directory('class-resources/' . $team->id)
                            ->visibility('private')
                            ->downloadable()
                            ->previewable()
                            ->columnSpan('full')
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record && $state) {
                                    // Handle file uploads to media library after saving
                                    collect($state)->each(function ($file) use ($record) {
                                        if ($file instanceof TemporaryUploadedFile) {
                                            $record->addMedia(storage_path('app/public/' . $file))
                                                ->toMediaCollection('files');
                                        } else {
                                            $record->addMediaFromDisk($file, 'public')
                                                ->toMediaCollection('files');
                                        }
                                    });
                                }
                            }),
                    ]),
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
                    
                Tables\Columns\TextColumn::make('access_level')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'success',
                        'teacher' => 'warning',
                        'owner' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => 'Everyone',
                        'teacher' => 'Teachers Only',
                        'owner' => 'Owner Only',
                    }),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                    
                Tables\Filters\SelectFilter::make('access_level')
                    ->options([
                        'all' => 'Everyone',
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
            'index' => Pages\ListClassResources::route('/'),
            'create' => Pages\CreateClassResource::route('/create'),
            'edit' => Pages\EditClassResource::route('/{record}/edit'),
            'view' => Pages\ViewClassResource::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $team = Filament::getTenant();
        
        return parent::getEloquentQuery()
            ->where('team_id', $team->id)
            ->with(['category', 'creator']);
    }
} 