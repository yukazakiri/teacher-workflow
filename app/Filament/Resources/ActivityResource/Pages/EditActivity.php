<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;
use Filament\Forms\Set;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            Tabs::make('Activity')
                ->tabs([
                    Tab::make('Basic Information')
                        ->schema([
                            Hidden::make('teacher_id')
                                ->default(fn () => Auth::id()),
                            Hidden::make('team_id')
                                ->default(fn () => Auth::user()->currentTeam->id),
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255),
                            Select::make('activity_type_id')
                                ->relationship('activityType', 'name')
                                ->required(),
                            RichEditor::make('description')
                                ->columnSpanFull(),
                            RichEditor::make('instructions')
                                ->columnSpanFull(),
                            Select::make('category')
                                ->options([
                                    'written' => 'Written',
                                    'performance' => 'Performance',
                                ])
                                ->required(),
                            Select::make('mode')
                                ->options([
                                    'individual' => 'Individual',
                                    'group' => 'Group',
                                    'take_home' => 'Take Home',
                                ])
                                ->required(),
                            TextInput::make('total_points')
                                ->numeric()
                                ->required(),
                            DateTimePicker::make('deadline')
                                ->nullable(),
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
                    
                    Tab::make('Submission Options')
                        ->schema([
                            Section::make('Submission Type')
                                ->schema([
                                    Radio::make('submission_type')
                                        ->label('How will students submit this activity?')
                                        ->options([
                                            'form' => 'Online Form - Students will fill out a form with questions',
                                            'resource' => 'Resource Upload - Students will upload files or submit text',
                                            'manual' => 'Manual Scoring Only - No student submissions, teacher will record scores manually',
                                        ])
                                        ->default('resource')
                                        ->required()
                                        ->reactive(),
                                ]),
                            
                            Section::make('Resource Upload Options')
                                ->schema([
                                    Toggle::make('allow_file_uploads')
                                        ->label('Allow File Uploads')
                                        ->default(true)
                                        ->reactive(),
                                    
                                    CheckboxList::make('allowed_file_types')
                                        ->label('Allowed File Types')
                                        ->options([
                                            'application/pdf' => 'PDF Documents',
                                            'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word Documents',
                                            'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel Spreadsheets',
                                            'application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint Presentations',
                                            'image/jpeg,image/png,image/gif' => 'Images (JPEG, PNG, GIF)',
                                            'video/mp4,video/quicktime' => 'Videos (MP4, QuickTime)',
                                            'audio/mpeg,audio/wav' => 'Audio Files (MP3, WAV)',
                                            'text/plain' => 'Text Files',
                                            'application/zip,application/x-rar-compressed' => 'Archives (ZIP, RAR)',
                                        ])
                                        ->columns(2)
                                        ->visible(fn (Get $get) => $get('submission_type') === 'resource' && $get('allow_file_uploads')),
                                    
                                    TextInput::make('max_file_size')
                                        ->label('Maximum File Size (MB)')
                                        ->numeric()
                                        ->default(10)
                                        ->visible(fn (Get $get) => $get('submission_type') === 'resource' && $get('allow_file_uploads')),
                                ])
                                ->visible(fn (Get $get) => $get('submission_type') === 'resource'),
                            
                            Section::make('Form Structure')
                                ->schema([
                                    Builder::make('form_structure')
                                        ->label('Form Questions')
                                        ->blocks([
                                            Block::make('text')
                                                ->label('Text Input')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Field Name')
                                                        ->required(),
                                                    TextInput::make('label')
                                                        ->label('Question Label')
                                                        ->required(),
                                                    Textarea::make('help_text')
                                                        ->label('Help Text')
                                                        ->nullable(),
                                                    Toggle::make('required')
                                                        ->label('Required')
                                                        ->default(false),
                                                ]),
                                            
                                            Block::make('textarea')
                                                ->label('Text Area')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Field Name')
                                                        ->required(),
                                                    TextInput::make('label')
                                                        ->label('Question Label')
                                                        ->required(),
                                                    Textarea::make('help_text')
                                                        ->label('Help Text')
                                                        ->nullable(),
                                                    Toggle::make('required')
                                                        ->label('Required')
                                                        ->default(false),
                                                    TextInput::make('rows')
                                                        ->label('Number of Rows')
                                                        ->numeric()
                                                        ->default(5),
                                                ]),
                                            
                                            Block::make('select')
                                                ->label('Multiple Choice')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Field Name')
                                                        ->required(),
                                                    TextInput::make('label')
                                                        ->label('Question Label')
                                                        ->required(),
                                                    Textarea::make('help_text')
                                                        ->label('Help Text')
                                                        ->nullable(),
                                                    Toggle::make('required')
                                                        ->label('Required')
                                                        ->default(false),
                                                    KeyValue::make('options')
                                                        ->label('Options')
                                                        ->keyLabel('Value')
                                                        ->valueLabel('Label')
                                                        ->addButtonLabel('Add Option')
                                                        ->required(),
                                                    Toggle::make('multiple')
                                                        ->label('Allow Multiple Selections')
                                                        ->default(false),
                                                ]),
                                            
                                            Block::make('checkbox')
                                                ->label('Checkbox')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Field Name')
                                                        ->required(),
                                                    TextInput::make('label')
                                                        ->label('Question Label')
                                                        ->required(),
                                                    Textarea::make('help_text')
                                                        ->label('Help Text')
                                                        ->nullable(),
                                                ]),
                                            
                                            Block::make('radio')
                                                ->label('Radio Buttons')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Field Name')
                                                        ->required(),
                                                    TextInput::make('label')
                                                        ->label('Question Label')
                                                        ->required(),
                                                    Textarea::make('help_text')
                                                        ->label('Help Text')
                                                        ->nullable(),
                                                    Toggle::make('required')
                                                        ->label('Required')
                                                        ->default(false),
                                                    KeyValue::make('options')
                                                        ->label('Options')
                                                        ->keyLabel('Value')
                                                        ->valueLabel('Label')
                                                        ->addButtonLabel('Add Option')
                                                        ->required(),
                                                ]),
                                            
                                            Block::make('date')
                                                ->label('Date Input')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Field Name')
                                                        ->required(),
                                                    TextInput::make('label')
                                                        ->label('Question Label')
                                                        ->required(),
                                                    Textarea::make('help_text')
                                                        ->label('Help Text')
                                                        ->nullable(),
                                                    Toggle::make('required')
                                                        ->label('Required')
                                                        ->default(false),
                                                ]),
                                            
                                            Block::make('file')
                                                ->label('File Upload')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Field Name')
                                                        ->required(),
                                                    TextInput::make('label')
                                                        ->label('Question Label')
                                                        ->required(),
                                                    Textarea::make('help_text')
                                                        ->label('Help Text')
                                                        ->nullable(),
                                                    Toggle::make('required')
                                                        ->label('Required')
                                                        ->default(false),
                                                    TextInput::make('max_size')
                                                        ->label('Maximum File Size (MB)')
                                                        ->numeric()
                                                        ->default(5),
                                                    TextInput::make('accepted_file_types')
                                                        ->label('Accepted File Types')
                                                        ->placeholder('e.g., .pdf,.doc,.jpg')
                                                        ->nullable(),
                                                ]),
                                        ])
                                        ->collapsible()
                                        ->visible(fn (Get $get) => $get('submission_type') === 'form'),
                                ]),
                            
                            Section::make('Teacher Submission Options')
                                ->schema([
                                    Toggle::make('allow_teacher_submission')
                                        ->label('Allow Teacher Submissions')
                                        ->helperText('If enabled, teachers can submit on behalf of students')
                                        ->default(false),
                                ])
                                ->visible(fn (Get $get) => $get('submission_type') !== 'manual'),
                        ]),
                    
                    Tab::make('Resources')
                        ->schema([
                            Section::make('Activity Resources')
                                ->schema([
                                    Placeholder::make('resources_info')
                                        ->content('Upload files that will be available to students for this activity.'),
                                    
                                    Repeater::make('resources')
                                        ->relationship('resources')
                                        ->schema([
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255),
                                            Textarea::make('description')
                                                ->nullable(),
                                            FileUpload::make('file_path')
                                                ->label('File')
                                                ->disk('public')
                                                ->directory(function ($record, $get) {
                                                    $activityId = $this->record->id;
                                                    return "resources/{$activityId}";
                                                })
                                                ->visibility('public')
                                                ->required()
                                                ->afterStateUpdated(function (Set $set, $state) {
                                                    if ($state) {
                                                        $file = \Illuminate\Http\UploadedFile::createFromBase($state);
                                                        $set('file_name', $file->getClientOriginalName());
                                                        $set('file_size', $file->getSize());
                                                        $set('file_type', $file->getMimeType());
                                                    }
                                                }),
                                            Hidden::make('file_name'),
                                            Hidden::make('file_size'),
                                            Hidden::make('file_type'),
                                            Hidden::make('user_id')
                                                ->default(fn () => Auth::id()),
                                            Toggle::make('is_public')
                                                ->label('Visible to Students')
                                                ->default(true),
                                        ])
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Resource')
                                        ->addActionLabel('Add Resource')
                                        ->collapsible(),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ];
    }
}
