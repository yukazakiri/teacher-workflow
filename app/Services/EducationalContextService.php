<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Jetstream\Jetstream;

/**
 * Service class for handling educational context data operations.
 */
class EducationalContextService
{
    /**
     * Status constants for consistent usage.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_GRADUATED = 'graduated';
    public const STATUS_COMPLETED = 'completed';
    
    /**
     * Valid student statuses.
     *
     * @var array<string>
     */
    public const VALID_STUDENT_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_GRADUATED,
    ];
    
    /**
     * Get students filtered by status.
     *
     * @param Team $team
     * @param string|null $status
     * @return Collection<int, Student>
     */
    public function getStudentsByStatus(Team $team, ?string $status = null): Collection
    {
        $query = $team->students();
        
        if ($status && in_array($status, self::VALID_STUDENT_STATUSES)) {
            $query->where('status', $status);
        }
        
        return $query->get();
    }
    
    /**
     * Get detailed information about a specific student.
     *
     * @param Team $team
     * @param string $studentId
     * @return Student|null
     */
    public function getStudentDetails(Team $team, string $studentId): ?Student
    {
        return $team->students()
            ->with([
                'user', 
                'activitySubmissions', 
                'examSubmissions', 
                'groupAssignments'
            ])
            ->where('id', $studentId)
            ->first();
    }
    
    /**
     * Get all members in the team with their roles.
     *
     * @param Team $team
     * @return Collection<int, User>
     */
    public function getTeamMembers(Team $team): Collection
    {
        return $team->users;
    }
    
    /**
     * Get statistics about the team.
     *
     * @param Team $team
     * @return array<string, mixed>
     */
    public function getTeamStatistics(Team $team): array
    {
        // Load relationships for counting
        $team->loadCount([
            'students', 
            'users', 
            'exams', 
            'activities',
            'resourceCategories',
            'classResources'
        ]);
        
        // Get counts by student status
        $studentStatusCounts = $team->students()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        return [
            'team' => $team,
            'studentStatusCounts' => $studentStatusCounts
        ];
    }
    
    /**
     * Get progress information for a specific student.
     *
     * @param Team $team
     * @param string $studentId
     * @return Student|null
     */
    public function getStudentProgressById(Team $team, string $studentId): ?Student
    {
        return $team->students()
            ->with(['activityProgress', 'examSubmissions', 'activitySubmissions'])
            ->where('id', $studentId)
            ->first();
    }
    
    /**
     * Get progress information for all students in a team.
     *
     * @param Team $team
     * @return Collection<int, Student>
     */
    public function getAllStudentsProgress(Team $team): Collection
    {
        return $team->students()
            ->with(['activityProgress', 'examSubmissions', 'activitySubmissions'])
            ->get();
    }
    
    /**
     * Get all pending invitations for the team.
     *
     * @param Team $team
     * @return Collection
     */
    public function getTeamInvitations(Team $team): Collection
    {
        $invitationClass = Jetstream::teamInvitationModel();
        return $invitationClass::where('team_id', $team->id)->get();
    }
    
    /**
     * Calculate a student's activity progress.
     *
     * @param Student $student
     * @param Team $team
     * @return array<string, int|float>
     */
    public function calculateStudentActivityProgress(Student $student, Team $team): array
    {
        $totalActivities = $team->activities()->count();
        $completedActivities = $student->activitySubmissions()
            ->where('status', self::STATUS_COMPLETED)
            ->count();
        
        $completionPercentage = $totalActivities > 0 
            ? round(($completedActivities / $totalActivities) * 100, 2) 
            : 0;
        
        return [
            'total_activities' => $totalActivities,
            'completed_activities' => $completedActivities,
            'pending_activities' => $totalActivities - $completedActivities,
            'completion_percentage' => $completionPercentage,
        ];
    }
    
    /**
     * Calculate a student's exam progress.
     *
     * @param Student $student
     * @param Team $team
     * @return array<string, int|float>
     */
    public function calculateStudentExamProgress(Student $student, Team $team): array
    {
        $totalExams = $team->exams()->count();
        $completedExams = $student->examSubmissions()
            ->where('status', self::STATUS_COMPLETED)
            ->count();
        
        $completionPercentage = $totalExams > 0 
            ? round(($completedExams / $totalExams) * 100, 2) 
            : 0;
        
        return [
            'total_exams' => $totalExams,
            'completed_exams' => $completedExams,
            'pending_exams' => $totalExams - $completedExams,
            'completion_percentage' => $completionPercentage,
        ];
    }
    
    /**
     * Format percentage to always show one decimal place.
     *
     * @param float $percentage
     * @return string
     */
    public function formatPercentage(float $percentage): string
    {
        return number_format($percentage, 1);
    }
    
    /**
     * Get emoji for student status.
     *
     * @param string $status
     * @return string
     */
    public function getStatusEmoji(string $status): string
    {
        return match($status) {
            self::STATUS_ACTIVE => '🟢',
            self::STATUS_INACTIVE => '🔴',
            self::STATUS_GRADUATED => '🎓',
            default => '❓'
        };
    }
    
    /**
     * Generate a visual progress bar in markdown.
     *
     * @param float $percentage
     * @return string
     */
    public function generateProgressBar(float $percentage): string
    {
        $percentage = min(100, max(0, $percentage));
        $filledCount = (int)round($percentage / 10);
        $emptyCount = 10 - $filledCount;
        
        $filled = str_repeat('█', $filledCount);
        $empty = str_repeat('░', $emptyCount);
        
        return "`{$filled}{$empty}` {$this->formatPercentage($percentage)}%";
    }
}
