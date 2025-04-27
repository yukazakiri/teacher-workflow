<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as PagesDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Facades\FilamentIcon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\ActivityProgress;
use App\Models\Attendance;
use Carbon\Carbon;

class StudentDashboard extends PagesDashboard
{
    protected static string $routePath = "/student-dashboard";

    protected static ?int $navigationSort = -1;

    public static function getNavigationLabel(): string
    {
        return __("Student Dashboard");
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return static::$navigationIcon ?? 
            (FilamentIcon::resolve("panels::pages.dashboard.navigation-item") ?? 
                (Filament::hasTopNavigation() 
                    ? "heroicon-m-academic-cap"
                    : "heroicon-o-academic-cap"));
    }

    public function getTitle(): string|Htmlable
    {
        return "Welcome, " . Auth::user()->name;
    }

    /**
     * Get data for the view.
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        
        // Get the student record associated with the current user
        $student = Student::where('user_id', $user->id)
            ->where('team_id', $team->id)
            ->first();
        
        if (!$student) {
            return [
                'heading' => $this->getHeading(),
                'subheading' => $this->getSubheading(),
                'hasStudentProfile' => false,
            ];
        }
        
        // Get attendance data
        $attendanceData = $this->getAttendanceData($student);
        
        // Get academic data
        $academicData = $this->getAcademicData($student);
        
        // Get upcoming assignments
        $upcomingAssignments = $this->getUpcomingAssignments($student);
        
        // Get recent grades
        $recentGrades = $this->getRecentGrades($student);
        
        return [
            'heading' => $this->getHeading(),
            'subheading' => $this->getSubheading(),
            'hasStudentProfile' => true,
            'student' => $student,
            'attendance' => $attendanceData,
            'academics' => $academicData,
            'upcomingAssignments' => $upcomingAssignments,
            'recentGrades' => $recentGrades,
        ];
    }
    
    /**
     * Get attendance data for the student
     */
    private function getAttendanceData(Student $student): array
    {
        $now = Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        
        // Get all attendance records
        $attendances = Attendance::where('student_id', $student->id)
            ->orderBy('date', 'desc')
            ->get();
        
        // Get recent attendance (last 30 days)
        $recentAttendances = Attendance::where('student_id', $student->id)
            ->where('date', '>=', $startOfMonth)
            ->orderBy('date', 'desc')
            ->get();
        
        // Calculate attendance statistics
        $totalAttendances = $attendances->count();
        $presentDays = $attendances->filter(function ($attendance) {
            return $attendance->isPresent();
        })->count();
        
        $lateDays = $attendances->filter(function ($attendance) {
            return $attendance->isLate();
        })->count();
        
        $absentDays = $attendances->filter(function ($attendance) {
            return $attendance->isAbsent();
        })->count();
        
        $excusedDays = $attendances->filter(function ($attendance) {
            return $attendance->isExcused();
        })->count();
        
        // Calculate attendance percentage
        $attendancePercentage = $totalAttendances > 0 
            ? round(($presentDays / $totalAttendances) * 100) 
            : 0;
        
        // Find the last absent date
        $lastAbsent = $attendances->first(function ($attendance) {
            return $attendance->isAbsent() || $attendance->isLate();
        });
        
        // Format attendance data for the calendar view
        $calendarData = [];
        foreach ($recentAttendances as $attendance) {
            $status = 'absent';
            if ($attendance->isPresent()) {
                $status = 'present';
            } elseif ($attendance->isLate()) {
                $status = 'late';
            } elseif ($attendance->isExcused()) {
                $status = 'excused';
            }
            
            $calendarData[] = [
                'date' => $attendance->date->format('Y-m-d'),
                'status' => $status,
            ];
        }
        
        return [
            'percentage' => $attendancePercentage,
            'days_present' => $presentDays,
            'days_total' => $totalAttendances,
            'last_absent' => $lastAbsent ? $lastAbsent->date->format('M d, Y') : null,
            'late_days' => $lateDays,
            'absent_days' => $absentDays,
            'excused_days' => $excusedDays,
            'recent' => $recentAttendances,
            'calendar' => $calendarData,
        ];
    }
    
    /**
     * Get academic data for the student
     */
    private function getAcademicData(Student $student): array
    {
        // Get all activities for the student's team
        $activities = Activity::where('team_id', $student->team_id)
            ->where('status', 'published')
            ->get();
        
        // Get student's activity submissions
        $submissions = ActivitySubmission::where('student_id', $student->id)->get();
        
        // Get student's activity progress
        $progress = ActivityProgress::where('student_id', $student->id)->get();
        
        $totalActivities = $activities->count();
        $submittedActivities = $submissions->filter(function ($submission) {
            return $submission->status === 'submitted' || $submission->status === 'completed';
        })->count();
        
        $completedActivities = $submissions->filter(function ($submission) {
            return $submission->status === 'completed';
        })->count();
        
        $gradedSubmissions = $submissions->filter(function ($submission) {
            return $submission->score !== null;
        });
        
        // Calculate average grade if there are graded submissions
        $averageGrade = 0;
        $letterGrade = 'N/A';
        
        if ($gradedSubmissions->count() > 0) {
            $totalScore = 0;
            $totalPossible = 0;
            
            foreach ($gradedSubmissions as $submission) {
                $activity = $submission->activity;
                if ($activity && $activity->total_points > 0) {
                    $totalScore += $submission->score;
                    $totalPossible += $activity->total_points;
                }
            }
            
            if ($totalPossible > 0) {
                $averageGrade = round(($totalScore / $totalPossible) * 100, 1);
                
                // Convert to letter grade
                if ($averageGrade >= 90) {
                    $letterGrade = 'A';
                } elseif ($averageGrade >= 80) {
                    $letterGrade = 'B';
                } elseif ($averageGrade >= 70) {
                    $letterGrade = 'C';
                } elseif ($averageGrade >= 60) {
                    $letterGrade = 'D';
                } else {
                    $letterGrade = 'F';
                }
            }
        }
        
        return [
            'total_activities' => $totalActivities,
            'submitted_activities' => $submittedActivities,
            'completed_activities' => $completedActivities,
            'graded_submissions' => $gradedSubmissions->count(),
            'average_grade' => $averageGrade,
            'letter_grade' => $letterGrade,
            'completion_rate' => $totalActivities > 0 
                ? round(($submittedActivities / $totalActivities) * 100) 
                : 0,
        ];
    }
    
    /**
     * Get upcoming assignments for the student
     */
    private function getUpcomingAssignments(Student $student): array
    {
        $now = Carbon::now();
        
        // Get upcoming activities with due dates
        $upcomingActivities = Activity::where('team_id', $student->team_id)
            ->where('status', 'published')
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $now)
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();
        
        $assignments = [];
        
        foreach ($upcomingActivities as $activity) {
            // Check if the student has already submitted this activity
            $submission = ActivitySubmission::where('activity_id', $activity->id)
                ->where('student_id', $student->id)
                ->first();
            
            $status = 'pending';
            if ($submission) {
                if ($submission->status === 'completed') {
                    $status = 'completed';
                } elseif ($submission->status === 'submitted') {
                    $status = 'submitted';
                } elseif ($submission->status === 'draft') {
                    $status = 'in_progress';
                }
            }
            
            $dueString = 'No due date';
            $colorClass = 'warning';
            
            if ($activity->due_date) {
                $daysUntilDue = $now->diffInDays($activity->due_date, false);
                
                if ($daysUntilDue < 0) {
                    $dueString = 'Overdue';
                    $colorClass = 'danger';
                } elseif ($daysUntilDue === 0) {
                    $dueString = 'Due today';
                    $colorClass = 'warning';
                } elseif ($daysUntilDue === 1) {
                    $dueString = 'Due tomorrow';
                    $colorClass = 'warning';
                } elseif ($daysUntilDue < 7) {
                    $dueString = 'Due this week';
                    $colorClass = 'info';
                } else {
                    $dueString = 'Due ' . $activity->due_date->format('M d');
                    $colorClass = 'gray';
                }
            }
            
            $assignments[] = [
                'id' => $activity->id,
                'title' => $activity->title,
                'due_date' => $activity->due_date,
                'due_string' => $dueString,
                'status' => $status,
                'color_class' => $colorClass,
                'format' => $activity->format,
                'total_points' => $activity->total_points,
            ];
        }
        
        return $assignments;
    }
    
    /**
     * Get recent grades for the student
     */
    private function getRecentGrades(Student $student): array
    {
        // Get recently graded submissions
        $recentGrades = ActivitySubmission::where('student_id', $student->id)
            ->whereNotNull('score')
            ->orderBy('graded_at', 'desc')
            ->take(5)
            ->get();
        
        $grades = [];
        
        foreach ($recentGrades as $submission) {
            $activity = $submission->activity;
            
            if (!$activity) {
                continue;
            }
            
            $percentage = 0;
            $letterGrade = 'N/A';
            
            if ($activity->total_points > 0) {
                $percentage = round(($submission->score / $activity->total_points) * 100);
                
                // Convert to letter grade
                if ($percentage >= 90) {
                    $letterGrade = 'A';
                } elseif ($percentage >= 80) {
                    $letterGrade = 'B';
                } elseif ($percentage >= 70) {
                    $letterGrade = 'C';
                } elseif ($percentage >= 60) {
                    $letterGrade = 'D';
                } else {
                    $letterGrade = 'F';
                }
            }
            
            $grades[] = [
                'id' => $submission->id,
                'activity_id' => $activity->id,
                'activity_title' => $activity->title,
                'score' => $submission->score,
                'total_points' => $activity->total_points,
                'percentage' => $percentage,
                'letter_grade' => $letterGrade,
                'submitted_at' => $submission->submitted_at,
                'graded_at' => $submission->graded_at,
                'feedback' => $submission->feedback,
            ];
        }
        
        return $grades;
    }
    
    /**
     * @return int | string | array<string, int | string | null>
     */
    public function getColumns(): int|string|array
    {
        return 1;
    }

    protected static string $view = 'filament.pages.student-dashboard';

    /**
     * Control access to this dashboard.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        
        if (!$team) {
            return false;
        }
        
        $membership = DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        
        return $membership && $membership->role === 'student';
    }
} 