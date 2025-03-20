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
use Illuminate\Support\Str;

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
                // Upload Files First - We'll auto-generate the title from the filename
                Forms\Components\Section::make('Upload Files')
                    ->description('Choose files to upload. The title will be auto-generated from the filename.')
                    ->schema([
                        FileUpload::make('files')
                            ->label('Select Files')
                            ->multiple()
                            ->maxFiles(10)
                            ->acceptedFileTypes([
                                'application/pdf', 
                                'image/*', 
                                'application/msword', 
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
                                'application/vnd.ms-excel', 
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                                'application/vnd.ms-powerpoint', 
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation', 
                                'text/plain', 
                                'application/zip', 
                                'application/x-rar-compressed'
                            ])
                            ->directory('class-resources/' . $team->id)
                            ->visibility('private')
                            ->downloadable()
                            ->previewable()
                            ->imageResizeMode('cover')
                            ->panelLayout('grid')
                            ->columnSpan('full')
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // If there's at least one file, set the title based on the first file
                                if (is_array($state) && count($state) > 0) {
                                    $filename = $state[0];
                                    
                                    if ($filename instanceof TemporaryUploadedFile) {
                                        $title = Str::replace(['_', '-'], ' ', $filename->getClientOriginalName());
                                        $title = pathinfo($title, PATHINFO_FILENAME);
                                        $title = Str::title(trim($title));
                                        
                                        $set('title', $title);
                                    }
                                }
                            }),
                    ])
                    ->columnSpan('full'),
                    
                Forms\Components\Group::make([
                    // Resource Details
                    Forms\Components\Section::make('Resource Details')
                        ->description('You can adjust the auto-generated title if needed')
                        ->schema([
                            Forms\Components\Hidden::make('team_id')
                                ->default(fn () => $team->id),
                                
                            Forms\Components\Hidden::make('created_by')
                                ->default(fn () => Auth::id()),
                            
                            Forms\Components\TextInput::make('title')
                                ->label('Resource Title')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Auto-generated from the filename. You can edit it if needed.')
                                ->columnSpan('full'),
                                
                            Forms\Components\Textarea::make('description')
                                ->rows(2)
                                ->placeholder('Optional: Add a brief description of this resource')
                                ->columnSpan('full'),
                        ])
                        ->collapsible(),
                    
                    // Categorization and Access Section
                    Forms\Components\Section::make('Category & Access')
                        ->schema([
                            Forms\Components\Select::make('category_id')
                                ->label('Category')
                                ->placeholder('Select a Category')
                                ->searchable()
                                ->required()
                                ->preload()
                                ->reactive()
                                ->options(function () use ($team) {
                                    return ResourceCategory::where('team_id', $team->id)
                                        ->orderBy('type')
                                        ->orderBy('sort_order')
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(function ($category) {
                                            $prefix = $category->type === 'teacher_material' 
                                                ? '🔒 Teacher: ' 
                                                : '📚 Student: ';
                                            
                                            return [$category->id => $prefix . $category->name];
                                        });
                                })
                                ->afterStateUpdated(function (callable $set, $state) use ($team) {
                                    // If a teacher material category is selected, set access level to teacher
                                    if ($state) {
                                        $category = ResourceCategory::find($state);
                                        if ($category && $category->type === 'teacher_material') {
                                            $set('access_level', 'teacher');
                                        }
                                    }
                                })
                                ->helperText('Choose a category for this resource'),
                                
                            Forms\Components\Select::make('access_level')
                                ->label('Who can access this resource?')
                                ->options([
                                    'all' => 'Everyone in class (Students & Teachers)',
                                    'teacher' => 'Teachers only',
                                    'owner' => 'Class owner only (Private)',
                                ])
                                ->default(function () {
                                    return 'all';
                                })
                                ->required()
                                ->reactive()
                                ->disabled(function ($get) use ($team) {
                                    $categoryId = $get('category_id');
                                    if (!$categoryId) {
                                        return false;
                                    }
                                    
                                    $category = ResourceCategory::find($categoryId);
                                    return $category && $category->type === 'teacher_material';
                                })
                                ->helperText(function ($get) use ($team) {
                                    $categoryId = $get('category_id');
                                    if (!$categoryId) {
                                        return 'Choose who can access this resource';
                                    }
                                    
                                    $category = ResourceCategory::find($categoryId);
                                    if ($category && $category->type === 'teacher_material') {
                                        return 'For Teacher Materials, access is automatically restricted to teachers or above';
                                    }
                                    
                                    return 'Choose who can access this resource';
                                }),
                        ]),
                ])
                    ->columnSpan('full'),
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
                    ->formatStateUsing(function ($state, ClassResource $record) {
                        $prefix = $record->category && $record->category->type === 'teacher_material' 
                            ? '🔒 ' 
                            : '📚 ';
                        
                        return $prefix . ($state ?? 'Uncategorized');
                    })
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('access_level')
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
                Tables\Filters\SelectFilter::make('category_type')
                    ->label('Resource Type')
                    ->options([
                        'teacher_material' => 'Teacher Materials',
                        'student_resource' => 'Student Resources',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        return $query->whereHas('category', function (Builder $query) use ($data) {
                            $query->where('type', $data['value']);
                        });
                    }),
                    
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
                    
                    Tables\Actions\BulkAction::make('change_access')
                        ->label('Change Access Level')
                        ->icon('heroicon-o-lock-closed')
                        ->form([
                            Forms\Components\Select::make('access_level')
                                ->label('New Access Level')
                                ->options([
                                    'all' => 'Everyone in class',
                                    'teacher' => 'Teachers only',
                                    'owner' => 'Class owner only',
                                ])
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $records->each(function (ClassResource $record) use ($data) {
                                $record->update(['access_level' => $data['access_level']]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
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
    
    public static function afterCreate(ClassResource $record, array $data): void
    {
        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $file) {
                if ($file instanceof TemporaryUploadedFile) {
                    $record->addMedia(storage_path('app/public/' . $file))
                        ->toMediaCollection('files');
                } else {
                    $record->addMediaFromDisk($file, 'public')
                        ->toMediaCollection('files');
                }
            }
        }
    }
} 