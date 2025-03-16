<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\QuestionResource\Pages;
use App\Filament\Admin\Resources\QuestionResource\RelationManagers;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->relationship('teacher', 'name')
                    ->required(),
                Forms\Components\Select::make('exam_id')
                    ->relationship('exam', 'title')
                    ->required(),
                Forms\Components\TextInput::make('team_id')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('points')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\Textarea::make('choices')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('correct_answer')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('explanation')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('rubric')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('word_limit')
                    ->numeric(),
                Forms\Components\Textarea::make('matching_pairs')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('answers')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('teacher.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('exam.title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('team_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('word_limit')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
