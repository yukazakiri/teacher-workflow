<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResource\Pages;
use App\Filament\Resources\ExamResource\RelationManagers;
use App\Models\Exam;
use App\Models\Question;
use App\Models\ExamQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\DB;

class ExamResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('team', function (Builder $query) {
                $query->where('id', Auth::user()->currentTeam->id);
            })
            ->where(function (Builder $query) {
                // Show all exams for the team owner, but only their own exams for other team members
                $isOwner = Auth::user()->currentTeam->user_id === Auth::id();
                if (!$isOwner) {
                    $query->where('teacher_id', Auth::id());
                }
            });
    }

    protected static ?string $model = Exam::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Assessments';
    protected static ?string $navigationLabel = 'Exams';
    protected static ?int $navigationSort = 1;

    protected static function getQuestionTypes(): array
    {
        return Question::TYPES;
    }

    protected static function getMultipleChoiceFields(): array
    {
        return [
            Textarea::make('content')
                ->label('Question')
                ->required()
                ->columnSpanFull(),
                KeyValue::make('choices')
                    ->label('Choices')
                    ->addButtonLabel('Add Choice')
                    ->keyLabel('Choice Label (e.g., A, B, C)')
                    ->valueLabel('Choice Content')
                    ->required()
                    ->default([
                        'A' => '',
                        'B' => '',
                        'C' => '',
                        'D' => '',
                    ])
                    ->addable(false)
                    ->editableKeys(false)
                    ->columnSpanFull(),
            Select::make('correct_answer')
                ->label('Correct Answer')
                ->options(fn (Get $get) => collect($get('choices'))->mapWithKeys(fn ($value, $key) => [$key => $key]))
                ->required()
                ->reactive(),
            TextInput::make('points')
                ->numeric()
                ->default(1)
                ->required(),
            // Textarea::make('explanation')
            //     ->label('Explanation')
            //     ->helperText('Explain why this is the correct answer'),
        ];
    }

    protected static function getTrueFalseFields(): array
    {
        return [
            Textarea::make('content')
                ->label('Question')
                ->required()
                ->columnSpanFull(),
            Grid::make()
                ->schema([
                    Radio::make('correct_answer')
                        ->label('Correct Answer')
                        ->options([
                            'true' => 'True',
                            'false' => 'False',
                        ])
                        ->required(),
                    TextInput::make('points')
                        ->numeric()
                        ->default(1)
                        ->required(),
                    // Textarea::make('explanation')
                    //     ->label('Explanation')
                    //     ->helperText('Explain why this is the correct answer'),
                ]),
        ];
    }

    protected static function getShortAnswerFields(): array
    {
        return [
            Textarea::make('content')
                ->label('Question')
                ->required()
                ->columnSpanFull(),
            TextInput::make('correct_answer')
                ->label('Correct Answer')
                ->required(),
            TextInput::make('points')
                ->numeric()
                ->default(1)
                ->required(),
            // Textarea::make('explanation')
            //     ->label('Explanation')
            //     ->helperText('Explain the correct answer'),
        ];
    }

    protected static function getEssayFields(): array
    {
        return [
            Textarea::make('content')
                ->label('Question')
                ->required()
                ->columnSpanFull(),
            RichEditor::make('rubric')
                ->label('Grading Rubric')
                ->required(),
            TextInput::make('points')
                ->numeric()
                ->default(5)
                ->required(),
            TextInput::make('word_limit')
                ->numeric()
                ->label('Minimum Word Limit')
                ->nullable(),
        ];
    }

    protected static function getMatchingFields(): array
    {
        return [
            Textarea::make('content')
                ->label('Instructions')
                ->required()
                ->columnSpanFull(),
            KeyValue::make('matching_pairs')
                ->label('Matching Pairs')
                ->addButtonLabel('Add Pair')
                ->keyLabel('Term')
                ->valueLabel('Definition')
                ->required()
                ->columnSpanFull(),
            TextInput::make('points')
                ->numeric()
                ->default(1)
                ->required(),
        ];
    }

    protected static function getFillInBlankFields(): array
    {
        return [
            Textarea::make('content')
                ->label('Question Text')
                ->helperText('Use [blank] to indicate where blanks should appear')
                ->required()
                ->columnSpanFull(),
            KeyValue::make('answers')
                ->label('Answers')
                ->addButtonLabel('Add Answer')
                ->keyLabel('Blank Number')
                ->valueLabel('Correct Answer')
                ->required()
                ->columnSpanFull(),
            TextInput::make('points')
                ->numeric()
                ->default(1)
                ->required(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Exam Details')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Hidden::make('teacher_id')
                                ->default(fn () => Auth::id()),
                            Hidden::make('team_id')
                                ->default(fn () => Auth::user()->currentTeam->id),
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter exam title')
                                ->columnSpan(2),
                            RichEditor::make('description')
                                ->placeholder('Enter exam description')
                                ->columnSpan(2),
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                    'archived' => 'Archived',
                                ])
                                ->default('draft')
                                ->required(),
                        ])
                        ->columns(2),
                    Step::make('Questions')
                        ->icon('heroicon-o-question-mark-circle')
                        ->schema([
                            Placeholder::make('instruction')
                                ->content('Click "Add Question Type" to add a section of questions. You can add multiple questions within each section.')
                                ->columnSpanFull(),

                            Forms\Components\Builder::make('question_sections')
                                ->label('Question Sections')
                                ->blocks([
                                    Forms\Components\Builder\Block::make('multiple_choice_section')
                                        ->label('Multiple Choice Questions')
                                        ->icon('heroicon-o-check-circle')
                                        ->schema([
                                            Placeholder::make('section_info')
                                                ->content('Add as many multiple choice questions as you need in this section.')
                                                ->columnSpanFull(),

                                            Repeater::make('questions')
                                                ->label('Multiple Choice Questions')
                                                ->schema(static::getMultipleChoiceFields())
                                                ->collapsible()
                                                ->itemLabel(fn (array $state): ?string =>
                                                    $state['content'] ? 'Q: ' . substr(strip_tags($state['content']), 0, 40) . '...' : 'New Multiple Choice Question'
                                                )
                                                ->addActionLabel('Add Multiple Choice Question')
                                                ->defaultItems(1)
                                                ->columnSpanFull()
                                        ]),

                                    Forms\Components\Builder\Block::make('true_false_section')
                                        ->label('True/False Questions')
                                        ->icon('heroicon-o-arrow-path')
                                        ->schema([
                                            Placeholder::make('section_info')
                                                ->content('Add as many true/false questions as you need in this section.')
                                                ->columnSpanFull(),

                                            Repeater::make('questions')
                                                ->label('True/False Questions')
                                                ->schema(static::getTrueFalseFields())
                                                ->collapsible()
                                                ->itemLabel(fn (array $state): ?string =>
                                                    $state['content'] ? 'Q: ' . substr(strip_tags($state['content']), 0, 40) . '...' : 'New True/False Question'
                                                )
                                                ->addActionLabel('Add True/False Question')
                                                ->defaultItems(1)
                                                ->columnSpanFull()
                                        ]),

                                    Forms\Components\Builder\Block::make('short_answer_section')
                                        ->label('Short Answer Questions')
                                        ->icon('heroicon-o-pencil')
                                        ->schema([
                                            Placeholder::make('section_info')
                                                ->content('Add as many short answer questions as you need in this section.')
                                                ->columnSpanFull(),

                                            Repeater::make('questions')
                                                ->label('Short Answer Questions')
                                                ->schema(static::getShortAnswerFields())
                                                ->collapsible()
                                                ->itemLabel(fn (array $state): ?string =>
                                                    $state['content'] ? 'Q: ' . substr(strip_tags($state['content']), 0, 40) . '...' : 'New Short Answer Question'
                                                )
                                                ->addActionLabel('Add Short Answer Question')
                                                ->defaultItems(1)
                                                ->columnSpanFull()
                                        ]),

                                    Forms\Components\Builder\Block::make('essay_section')
                                        ->label('Essay Questions')
                                        ->icon('heroicon-o-document-text')
                                        ->schema([
                                            Placeholder::make('section_info')
                                                ->content('Add as many essay questions as you need in this section.')
                                                ->columnSpanFull(),

                                            Repeater::make('questions')
                                                ->label('Essay Questions')
                                                ->schema(static::getEssayFields())
                                                ->collapsible()
                                                ->itemLabel(fn (array $state): ?string =>
                                                    $state['content'] ? 'Q: ' . substr(strip_tags($state['content']), 0, 40) . '...' : 'New Essay Question'
                                                )
                                                ->addActionLabel('Add Essay Question')
                                                ->defaultItems(1)
                                                ->columnSpanFull()
                                        ]),

                                    Forms\Components\Builder\Block::make('matching_section')
                                        ->label('Matching Questions')
                                        ->icon('heroicon-o-arrows-right-left')
                                        ->schema([
                                            Placeholder::make('section_info')
                                                ->content('Add as many matching questions as you need in this section.')
                                                ->columnSpanFull(),

                                            Repeater::make('questions')
                                                ->label('Matching Questions')
                                                ->schema(static::getMatchingFields())
                                                ->collapsible()
                                                ->itemLabel(fn (array $state): ?string =>
                                                    $state['content'] ? 'Q: ' . substr(strip_tags($state['content']), 0, 40) . '...' : 'New Matching Question'
                                                )
                                                ->addActionLabel('Add Matching Question')
                                                ->defaultItems(1)
                                                ->columnSpanFull()
                                        ]),

                                    Forms\Components\Builder\Block::make('fill_in_blank_section')
                                        ->label('Fill in the Blank Questions')
                                        ->icon('heroicon-o-square-3-stack-3d')
                                        ->schema([
                                            Placeholder::make('section_info')
                                                ->content('Add as many fill in the blank questions as you need in this section.')
                                                ->columnSpanFull(),

                                            Repeater::make('questions')
                                                ->label('Fill in the Blank Questions')
                                                ->schema(static::getFillInBlankFields())
                                                ->collapsible()
                                                ->itemLabel(fn (array $state): ?string =>
                                                    $state['content'] ? 'Q: ' . substr(strip_tags($state['content']), 0, 40) . '...' : 'New Fill in the Blank Question'
                                                )
                                                ->addActionLabel('Add Fill in the Blank Question')
                                                ->defaultItems(1)
                                                ->columnSpanFull()
                                        ]),
                                ])
                                ->addActionLabel('Add Question Type')
                                ->collapsible()
                                ->blockNumbers(false)
                                ->reorderableWithButtons()
                                ->columnSpanFull(),

                            Section::make('Question Summary')
                                ->schema([
                                    Placeholder::make('total_questions')
                                        ->label('Total Questions')
                                        ->content(function (Get $get): int {
                                            $sections = $get('question_sections') ?? [];
                                            $totalQuestions = 0;

                                            foreach ($sections as $section) {
                                                $totalQuestions += count($section['data']['questions'] ?? []);
                                            }

                                            return $totalQuestions;
                                        }),

                                    Placeholder::make('total_points')
                                        ->label('Total Points')
                                        ->content(function (Get $get): int {
                                            $sections = $get('question_sections') ?? [];
                                            $totalPoints = 0;

                                            foreach ($sections as $section) {
                                                foreach ($section['data']['questions'] ?? [] as $question) {
                                                    $totalPoints += $question['points'] ?? 0;
                                                }
                                            }

                                            return $totalPoints;
                                        }),
                                ])
                                ->columns(2),
                        ]),
                    Step::make('Preview & Export')
                        ->icon('heroicon-o-eye')
                        ->schema([
                            Section::make('Exam Preview')
                                ->schema([
                                    Placeholder::make('preview_title')
                                        ->label('Title')
                                        ->content(fn (Get $get) => $get('title')),
                                    Placeholder::make('preview_description')
                                        ->label('Description')
                                        ->content(fn (Get $get) => $get('description')),
                                    Placeholder::make('preview_questions')
                                        ->label('Number of Questions')
                                        ->content(function (Get $get): int {
                                            $sections = $get('question_sections') ?? [];
                                            $totalQuestions = 0;

                                            foreach ($sections as $section) {
                                                $totalQuestions += count($section['data']['questions'] ?? []);
                                            }

                                            return $totalQuestions;
                                        }),
                                    Placeholder::make('preview_total_points')
                                        ->label('Total Points')
                                        ->content(function (Get $get): int {
                                            $sections = $get('question_sections') ?? [];
                                            $totalPoints = 0;

                                            foreach ($sections as $section) {
                                                foreach ($section['data']['questions'] ?? [] as $question) {
                                                    $totalPoints += $question['points'] ?? 0;
                                                }
                                            }

                                            return $totalPoints;
                                        }),
                                ])
                                ->columns(2),
                            Section::make('Export Options')
                                ->schema([
                                    Toggle::make('include_answer_key')
                                        ->label('Include Answer Key')
                                        ->default(false),
                                    Select::make('export_format')
                                        ->label('Export Format')
                                        ->options([
                                            'pdf' => 'PDF',
                                            'docx' => 'Word (DOCX)',
                                        ])
                                        ->default('pdf'),
                                ])
                                ->columns(2),
                        ]),
                ])
                ->skippable()
                ->persistStepInQueryString()
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions'),
                Tables\Columns\TextColumn::make('total_points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'draft',
                        'success' => 'published',
                        'warning' => 'archived',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Exam $record) => route('exams.export', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Exam $record) {
                        $newExam = $record->replicate();
                        $newExam->title = "Copy of {$record->title}";
                        $newExam->status = 'draft';
                        $newExam->save();

                        // Duplicate questions
                        foreach ($record->questions as $question) {
                            $newQuestion = $question->replicate();
                            $newQuestion->exam_id = $newExam->id;
                            $newQuestion->save();
                        }

                        return redirect()->route('filament.admin.resources.exams.edit', $newExam);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(fn (\Illuminate\Support\Collection $records) => redirect()->route('exams.export-bulk', ['ids' => $records->pluck('id')->toArray()])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // We'll create these relation managers later
            // RelationManagers\QuestionsRelationManager::class,
            // RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'create' => Pages\CreateExam::route('/create'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }
}
