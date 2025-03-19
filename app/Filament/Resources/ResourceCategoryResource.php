<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceCategoryResource\Pages;
use App\Models\ResourceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class ResourceCategoryResource extends Resource
{
    protected static ?string $model = ResourceCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'Class Management';
    
    protected static ?int $navigationSort = 21;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Resource Categories';
    }
    
    public static function getPluralLabel(): string
    {
        return 'Resource Categories';
    }
    
    public static function getModelLabel(): string
    {
        return 'Resource Category';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Details')
                    ->schema([
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
                            
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\ColorColumn::make('color'),
                
                Tables\Columns\IconColumn::make('icon')
                    ->icon(fn (string $state): string => $state),
                    
                Tables\Columns\TextColumn::make('resources_count')
                    ->label('Resources')
                    ->counts('resources')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (ResourceCategory $record) {
                        // Set category_id to null for all resources in this category
                        $record->resources()->update(['category_id' => null]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (ResourceCategory $records) {
                            // Set category_id to null for all resources in these categories
                            foreach ($records as $record) {
                                $record->resources()->update(['category_id' => null]);
                            }
                        }),
                ]),
            ])
            ->defaultSort('sort_order');
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
            'index' => Pages\ListResourceCategories::route('/'),
            'create' => Pages\CreateResourceCategory::route('/create'),
            'edit' => Pages\EditResourceCategory::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $team = Filament::getTenant();
        
        return parent::getEloquentQuery()
            ->where('team_id', $team->id);
    }
} 