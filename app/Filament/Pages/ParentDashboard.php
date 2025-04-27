<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as PagesDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Support\Facades\FilamentIcon;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ParentStudentRelationship;
use App\Models\Student;
use App\Models\Activity;
use App\Models\ActivityProgress;
use App\Models\ActivitySubmission;
use App\Models\Attendance;
use Illuminate\Support\Carbon;

class ParentDashboard extends PagesDashboard
{
    protected static string $routePath = "/parent-dashboard";

    protected static ?int $navigationSort = -1;

    public static function getNavigationLabel(): string
    {
        return __("Parent Dashboard");
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return static::$navigationIcon ?? 
            (FilamentIcon::resolve("panels::pages.dashboard.navigation-item") ?? 
                (Filament::hasTopNavigation() 
                    ? "heroicon-m-home"
                    : "heroicon-o-home"));
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
        $linkedStudents = $user->linkedStudents;
        
        // Get detailed student information with real data
        $studentDetails = [];
        
        foreach ($linkedStudents as $student) {
            // Get attendance data
            $attendanceData = $this->getAttendanceData($student);
            
            // Get academic data
            $academicData = $this->getAcademicData($student);
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities($student);
            
            // Get team info
            $team = $student->team;
            
            $studentDetails[] = [
                'student' => $student,
                'attendance' => $attendanceData,
                'academics' => $academicData,
                'recent_activities' => $recentActivities,
                'team' => $team,
            ];
        }
        
        return [
            'heading' => $this->getHeading(),
            'subheading' => $this->getSubheading(),
            'hasLinkedStudents' => $user->hasLinkedStudents(),
            'linkedStudents' => $linkedStudents,
            'studentDetails' => $studentDetails,
        ];
    }
    
    /**
     * Get attendance data for a student
     */
    private function getAttendanceData(Student $student): array
    {
        $attendances = Attendance::where('student_id', $student->id)
            ->orderBy('date', 'desc')
            ->get();
        
        $totalAttendances = $attendances->count();
        
        if ($totalAttendances === 0) {
            return [
                'percentage' => 0,
                'days_present' => 0,
                'days_total' => 0,
                'last_absent' => null,
                'late_days' => 0,
                'absent_days' => 0,
                'excused_days' => 0,
                'recent' => [],
            ];
        }
        
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
        
        $attendancePercentage = $totalAttendances > 0 
            ? round((($presentDays + $lateDays) / $totalAttendances) * 100) 
            : 0;
            
        $lastAbsent = $attendances->first(function ($attendance) {
            return $attendance->isAbsent();
        });
        
        $recentAttendances = $attendances->take(5)->map(function ($attendance) {
            return [
                'date' => $attendance->date->format('M d, Y'),
                'status' => $attendance->status,
                'notes' => $attendance->notes,
            ];
        });
        
        return [
            'percentage' => $attendancePercentage,
            'days_present' => $presentDays,
            'days_total' => $totalAttendances,
            'last_absent' => $lastAbsent ? $lastAbsent->date->format('M d, Y') : null,
            'late_days' => $lateDays,
            'absent_days' => $absentDays,
            'excused_days' => $excusedDays,
            'recent' => $recentAttendances,
        ];
    }
    
    /**
     * Get academic data for a student
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
            return $submission->isSubmitted() || $submission->isCompleted();
        })->count();
        
        $completedActivities = $submissions->filter(function ($submission) {
            return $submission->isCompleted();
        })->count();
        
        $gradedSubmissions = $submissions->filter(function ($submission) {
            return $submission->isGraded();
        });
        
        // Calculate average grade if there are graded submissions
        $averageGrade = null;
        $letterGrade = 'N/A';
        
        if ($gradedSubmissions->count() > 0) {
            $totalScore = 0;
            $totalPoints = 0;
            
            foreach ($gradedSubmissions as $submission) {
                if ($submission->score !== null && $submission->activity->total_points > 0) {
                    $totalScore += $submission->score;
                    $totalPoints += $submission->activity->total_points;
                }
            }
            
            if ($totalPoints > 0) {
                $averageGrade = ($totalScore / $totalPoints) * 100;
                $letterGrade = $this->convertToLetterGrade($averageGrade);
            }
        }
        
        // Get upcoming activities (due in the future)
        $now = Carbon::now();
        $upcomingActivities = $activities->filter(function ($activity) use ($now, $submissions) {
            if (!$activity->due_date) {
                return false;
            }
            
            // Check if the activity is due in the future
            if ($activity->due_date->isFuture()) {
                // Check if the student hasn't submitted it yet
                $hasSubmitted = $submissions->contains(function ($submission) use ($activity) {
                    return $submission->activity_id === $activity->id && 
                        ($submission->isSubmitted() || $submission->isCompleted());
                });
                
                return !$hasSubmitted;
            }
            
            return false;
        })->take(5)->map(function ($activity) {
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'due_date' => $activity->due_date->format('M d, Y'),
                'days_remaining' => $activity->due_date->diffInDays(Carbon::now()),
                'total_points' => $activity->total_points,
            ];
        });
        
        // Get recent submissions
        $recentSubmissions = $submissions->sortByDesc('submitted_at')
            ->take(5)
            ->map(function ($submission) {
                $activity = $submission->activity;
                return [
                    'id' => $submission->id,
                    'activity_title' => $activity->title ?? 'Unknown Activity',
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('M d, Y') : 'Not submitted',
                    'score' => $submission->score,
                    'total_points' => $activity->total_points ?? 0,
                    'status' => $submission->status,
                    'feedback' => $submission->feedback,
                ];
            });
        
        return [
            'average_grade' => $averageGrade !== null ? round($averageGrade, 1) : null,
            'letter_grade' => $letterGrade,
            'total_activities' => $totalActivities,
            'submitted_activities' => $submittedActivities,
            'completed_activities' => $completedActivities,
            'upcoming_activities' => $upcomingActivities,
            'recent_submissions' => $recentSubmissions,
        ];
    }
    
    /**
     * Convert numerical grade to letter grade
     */
    private function convertToLetterGrade(float $percentage): string
    {
        if ($percentage >= 97) return 'A+';
        if ($percentage >= 93) return 'A';
        if ($percentage >= 90) return 'A-';
        if ($percentage >= 87) return 'B+';
        if ($percentage >= 83) return 'B';
        if ($percentage >= 80) return 'B-';
        if ($percentage >= 77) return 'C+';
        if ($percentage >= 73) return 'C';
        if ($percentage >= 70) return 'C-';
        if ($percentage >= 67) return 'D+';
        if ($percentage >= 63) return 'D';
        if ($percentage >= 60) return 'D-';
        return 'F';
    }
    
    /**
     * Get recent activities for a student
     */
    private function getRecentActivities(Student $student): array
    {
        // Get recent submissions
        $submissions = ActivitySubmission::where('student_id', $student->id)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
            
        $activities = [];
        
        foreach ($submissions as $submission) {
            $activity = $submission->activity;
            
            if (!$activity) {
                continue;
            }
            
            $status = match ($submission->status) {
                'completed' => 'success',
                'submitted' => 'primary',
                'late' => 'warning',
                default => 'gray'
            };
            
            $description = '';
            
            if ($submission->isGraded()) {
                $description = "Score: {$submission->score}/{$activity->total_points}";
                if ($submission->feedback) {
                    $description .= " - " . (strlen($submission->feedback) > 50 ? substr($submission->feedback, 0, 50) . '...' : $submission->feedback);
                }
            } elseif ($submission->isSubmitted()) {
                $description = "Submitted, awaiting grading";
            } elseif ($submission->isDraft()) {
                $description = "Draft saved";
            } elseif ($submission->isLate()) {
                $description = "Submitted late";
            }
            
            $activities[] = [
                'type' => 'submission',
                'title' => $activity->title,
                'description' => $description,
                'date' => $submission->updated_at->format('M d, Y'),
                'status' => $status,
            ];
        }
        
        // Get recent attendances
        $attendances = Attendance::where('student_id', $student->id)
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();
            
        foreach ($attendances as $attendance) {
            $status = match ($attendance->status) {
                'present' => 'success',
                'late' => 'warning',
                'absent' => 'danger',
                'excused' => 'primary',
                default => 'gray'
            };
            
            $title = "Attendance: " . ucfirst($attendance->status);
            $description = $attendance->notes ?? "No additional notes";
            
            $activities[] = [
                'type' => 'attendance',
                'title' => $title,
                'description' => $description,
                'date' => $attendance->date->format('M d, Y'),
                'status' => $status,
            ];
        }
        
        // Sort by date (newest first)
        usort($activities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return array_slice($activities, 0, 5);
    }

    /**
     * @return int | string | array<string, int | string | null>
     */
    public function getColumns(): int|string|array
    {
        return 1;
    }

    protected static string $view = 'filament.pages.parent-dashboard';

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
        
        return $membership && $membership->role === 'parent';
    }
}
