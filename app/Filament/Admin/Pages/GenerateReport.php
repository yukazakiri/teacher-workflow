<?php

namespace App\Filament\Admin\Pages;

use App\Models\Activity;
use App\Models\Student;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerateReport extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Generate Reports';

    protected static ?string $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.admin.pages.generate-report';

    public array $reportData = [];
    public bool $showReport = false;
    public ?string $reportType = null;
    public ?string $studentId = null;
    public ?string $activityId = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Report Criteria')
                ->schema([
                    Select::make('reportType')
                        ->label('Report Type')
                        ->options([
                            'student_profile' => 'Student Profile',
                            'activity_report' => 'Activity Report',
                            'student_activities' => 'Student Activities',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->resetExcept(['reportType'])),

                    Grid::make(2)
                        ->schema([
                            Select::make('studentId')
                                ->label('Student')
                                ->visible(fn ($get) => in_array($get('reportType'), ['student_profile', 'student_activities']))
                                ->options(fn () => Student::query()->pluck('name', 'id'))
                                ->searchable()
                                ->required(fn ($get) => in_array($get('reportType'), ['student_profile', 'student_activities'])),

                            Select::make('activityId')
                                ->label('Activity')
                                ->visible(fn ($get) => $get('reportType') === 'activity_report')
                                ->options(fn () => Activity::query()->pluck('title', 'id'))
                                ->searchable()
                                ->required(fn ($get) => $get('reportType') === 'activity_report'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            DatePicker::make('startDate')
                                ->label('Start Date')
                                ->visible(fn ($get) => in_array($get('reportType'), ['student_activities', 'activity_report'])),

                            DatePicker::make('endDate')
                                ->label('End Date')
                                ->visible(fn ($get) => in_array($get('reportType'), ['student_activities', 'activity_report'])),
                        ]),
                ]),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'reportType' => $this->reportType,
            'reportData' => $this->reportData,
            'showReport' => $this->showReport,
        ];
    }

    public function generateReport(): void
    {
        $this->validate();

        switch ($this->reportType) {
            case 'student_profile':
                $this->generateStudentProfile();
                break;
            case 'activity_report':
                $this->generateActivityReport();
                break;
            case 'student_activities':
                $this->generateStudentActivities();
                break;
        }

        $this->showReport = true;

        Notification::make()
            ->title('Report Generated')
            ->success()
            ->send();
    }

    /**
     * Handle the PDF export action
     */
    public function exportPdf(): void
    {
        if (empty($this->reportData)) {
            Notification::make()
                ->title('No report data available')
                ->warning()
                ->send();
            return;
        }

        $pdf = Pdf::loadView('filament.admin.reports.pdf', [
            'reportType' => $this->reportType,
            'reportData' => $this->reportData,
        ]);

        $filename = $this->getReportFilename();
        
        $pdf->save(storage_path('app/public/' . $filename));
        chmod(storage_path('app/public/' . $filename), 0644); // Make file readable
        
        $url = url('storage/' . $filename);
        
        Notification::make()
            ->title('PDF Report Generated')
            ->body('Your report has been generated. Click to download.')
            ->actions([
                NotificationAction::make('download')
                    ->label('Download')
                    ->url($url)
                    ->openUrlInNewTab()
            ])
            ->success()
            ->send();
    }

    private function getReportFilename(): string
    {
        $timestamp = now()->format('Y-m-d_H-i');

        return match ($this->reportType) {
            'student_profile' => "student_profile_{$this->reportData['student']['student_id']}_{$timestamp}.pdf",
            'activity_report' => "activity_report_{$timestamp}.pdf",
            'student_activities' => "student_activities_{$this->reportData['student']['student_id']}_{$timestamp}.pdf",
            default => "report_{$timestamp}.pdf",
        };
    }

    private function generateStudentProfile(): void
    {
        $student = Student::with(['user', 'activitySubmissions', 'examSubmissions', 'attendances'])
            ->findOrFail($this->studentId);

        $this->reportData = [
            'student' => $student->toArray(),
            'attendances' => $student->attendances->toArray(),
            'activitySubmissions' => $student->activitySubmissions->toArray(),
            'examSubmissions' => $student->examSubmissions->toArray(),
        ];
    }

    private function generateActivityReport(): void
    {
        $activity = Activity::with(['submissions', 'submissions.student', 'activityType', 'teacher'])
            ->findOrFail($this->activityId);

        $query = $activity->submissions();

        if ($this->startDate) {
            $query->where('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('created_at', '<=', $this->endDate);
        }

        $submissions = $query->get();

        $this->reportData = [
            'activity' => $activity->toArray(),
            'submissions' => $submissions->toArray(),
            'teacher' => $activity->teacher->toArray(),
            'submissionCount' => $submissions->count(),
            'averageScore' => $submissions->avg('score'),
            'maxScore' => $submissions->max('score'),
            'minScore' => $submissions->min('score'),
        ];
    }

    private function generateStudentActivities(): void
    {
        $student = Student::with(['user'])->findOrFail($this->studentId);

        $query = $student->activitySubmissions()->with('activity');

        if ($this->startDate) {
            $query->where('created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->where('created_at', '<=', $this->endDate);
        }

        $submissions = $query->get();

        $this->reportData = [
            'student' => $student->toArray(),
            'submissions' => $submissions->toArray(),
            'submissionCount' => $submissions->count(),
            'averageScore' => $submissions->avg('score'),
            'activitiesCompleted' => $submissions->count(),
            'dateRange' => [
                'start' => $this->startDate,
                'end' => $this->endDate,
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Generate Report')
                ->action('generateReport')
                ->color('primary'),

            Action::make('export')
                ->label('Export PDF')
                ->action('exportPdf')
                ->color('success')
                ->visible(fn () => $this->showReport),
        ];
    }
}