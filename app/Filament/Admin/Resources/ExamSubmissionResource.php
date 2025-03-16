<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ExamSubmissionResource\Pages;
use App\Filament\Admin\Resources\ExamSubmissionResource\RelationManagers;
use App\Models\ExamSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExamSubmissionResource extends Resource
{
    protected static ?string $model = ExamSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('exam_id')
                    ->relationship('exam', 'title')
                    ->required(),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name')
                    ->required(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('score')
                    ->numeric(),
                Forms\Components\Textarea::make('feedback')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('started_at'),
                Forms\Components\DateTimePicker::make('submitted_at'),
                Forms\Components\TextInput::make('final_grade')
                    ->numeric(),
                Forms\Components\TextInput::make('graded_by'),
                Forms\Components\DateTimePicker::make('graded_at'),
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
                Tables\Columns\TextColumn::make('exam.title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('final_grade')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('graded_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('graded_at')
                    ->dateTime()
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
            'index' => Pages\ListExamSubmissions::route('/'),
            'create' => Pages\CreateExamSubmission::route('/create'),
            'edit' => Pages\EditExamSubmission::route('/{record}/edit'),
        ];
    }
}
