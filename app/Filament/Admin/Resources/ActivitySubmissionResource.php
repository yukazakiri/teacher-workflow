<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivitySubmissionResource\Pages;
use App\Models\ActivitySubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitySubmissionResource extends Resource
{
    protected static ?string $model = ActivitySubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('activity_id')
                    ->relationship('activity', 'title')
                    ->required(),
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'name'),
                Forms\Components\Select::make('group_id')
                    ->relationship('group', 'name'),
                Forms\Components\Textarea::make('content')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('attachments')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\TextInput::make('score')
                    ->numeric(),
                Forms\Components\Textarea::make('feedback')
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('submitted_at'),
                Forms\Components\TextInput::make('graded_by'),
                Forms\Components\DateTimePicker::make('graded_at'),
                Forms\Components\TextInput::make('final_grade')
                    ->numeric(),
                Forms\Components\Textarea::make('form_responses')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('submitted_by_teacher')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activity.title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('score')
                    ->numeric()
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
                Tables\Columns\TextColumn::make('graded_by')
                    ->searchable(),
                Tables\Columns\TextColumn::make('graded_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('final_grade')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('submitted_by_teacher')
                    ->boolean(),
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
            'index' => Pages\ListActivitySubmissions::route('/'),
            'create' => Pages\CreateActivitySubmission::route('/create'),
            'edit' => Pages\EditActivitySubmission::route('/{record}/edit'),
        ];
    }
}
