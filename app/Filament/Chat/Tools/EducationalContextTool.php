<?php

declare(strict_types=1);

namespace App\Filament\Chat\Tools;

use App\Models\Student;
use App\Models\Team;
use App\Models\User;
use App\Services\EducationalContextService;
use AssistantEngine\OpenFunctions\Core\Contracts\AbstractOpenFunction;
use AssistantEngine\OpenFunctions\Core\Helpers\FunctionDefinition;
use AssistantEngine\OpenFunctions\Core\Helpers\Parameter;
use AssistantEngine\OpenFunctions\Core\Models\Responses\TextResponseItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Educational Context Tool for AI Assistant.
 *
 * This tool provides educational context information about students, teams, and progress
 * through a set of functions that can be called by the AI assistant.
 */
class EducationalContextTool extends AbstractOpenFunction
{
    /**
     * The educational context service.
     */
    private EducationalContextService $service;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->service = new EducationalContextService;
    }

    /**
     * Generate function definitions for the educational context tool.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generateFunctionDefinitions(): array
    {
        $functions = [];

        // Get students by status
        $functions[] = $this->createStudentsByStatusFunction();

        // Add an alias for getting current team students
        $functions[] = $this->createCurrentTeamStudentsFunction();

        // Add an alias for getting current team name
        $functions[] = $this->createCurrentTeamNameFunction();

        // Get student details
        $functions[] = $this->createStudentDetailsFunction();

        // Get team members
        $functions[] = $this->createTeamMembersFunction();

        // Get team statistics
        $functions[] = $this->createTeamStatisticsFunction();

        // Get student progress
        $functions[] = $this->createStudentProgressFunction();

        // Get team invitations
        $functions[] = $this->createTeamInvitationsFunction();

        return $functions;
    }

    /**
     * Create function definition for getting students by status.
     *
     * @return array<string, mixed>
     */
    private function createStudentsByStatusFunction(): array
    {
        $studentsByStatus = new FunctionDefinition(
            'getStudentsByStatus',
            'Get a list of students filtered by their status (active, inactive, graduated).'
        );
        $studentsByStatus->addParameter(
            Parameter::string('status')
                ->description('The status to filter students by (active, inactive, graduated). If not provided, returns all students.')
                ->required(false)
        );

        return $studentsByStatus->createFunctionDescription();
    }

    /**
     * Create function definition for getting current team students.
     *
     * @return array<string, mixed>
     */
    private function createCurrentTeamStudentsFunction(): array
    {
        $currentTeamStudents = new FunctionDefinition(
            'get_current_team_students',
            'Get a list of all students in the current team.'
        );

        return $currentTeamStudents->createFunctionDescription();
    }

    /**
     * Create function definition for getting current team name.
     *
     * @return array<string, mixed>
     */
    private function createCurrentTeamNameFunction(): array
    {
        $currentTeamName = new FunctionDefinition(
            'get_current_team_name',
            'Get the name of the current team.'
        );

        return $currentTeamName->createFunctionDescription();
    }

    /**
     * Create function definition for getting student details.
     *
     * @return array<string, mixed>
     */
    private function createStudentDetailsFunction(): array
    {
        $studentDetails = new FunctionDefinition(
            'getStudentDetails',
            'Get detailed information about a specific student.'
        );
        $studentDetails->addParameter(
            Parameter::string('studentId')
                ->description('The ID of the student to get details for.')
                ->required(true)
        );

        return $studentDetails->createFunctionDescription();
    }

    /**
     * Create function definition for getting team members.
     *
     * @return array<string, mixed>
     */
    private function createTeamMembersFunction(): array
    {
        $teamMembers = new FunctionDefinition(
            'getTeamMembers',
            'Get a list of all members in the current team with their roles.'
        );

        return $teamMembers->createFunctionDescription();
    }

    /**
     * Create function definition for getting team statistics.
     *
     * @return array<string, mixed>
     */
    private function createTeamStatisticsFunction(): array
    {
        $teamStats = new FunctionDefinition(
            'getTeamStatistics',
            'Get statistics about the current team (number of students, members, etc.).'
        );

        return $teamStats->createFunctionDescription();
    }

    /**
     * Create function definition for getting student progress.
     *
     * @return array<string, mixed>
     */
    private function createStudentProgressFunction(): array
    {
        $studentProgress = new FunctionDefinition(
            'getStudentProgress',
            'Get progress information for a specific student or all students.'
        );
        $studentProgress->addParameter(
            Parameter::string('studentId')
                ->description('The ID of the student to get progress for. If not provided, returns progress for all students.')
                ->required(false)
        );

        return $studentProgress->createFunctionDescription();
    }

    /**
     * Create function definition for getting team invitations.
     *
     * @return array<string, mixed>
     */
    private function createTeamInvitationsFunction(): array
    {
        $teamInvitations = new FunctionDefinition(
            'getTeamInvitations',
            'Get a list of all pending invitations for the current team.'
        );

        return $teamInvitations->createFunctionDescription();
    }

    /**
     * Get students filtered by status.
     *
     * @param  array<string, mixed>  $params
     */
    public function getStudentsByStatus(array $params): TextResponseItem
    {
        try {
            $team = $this->getCurrentTeam();
            if (! $team) {
                return $this->createErrorResponse('No current team found. Please select a team first.');
            }

            $status = $params['status'] ?? null;
            $students = $this->service->getStudentsByStatus($team, $status);

            if ($students->isEmpty()) {
                return $this->createTextResponse("## No Students Found\n\nNo students found with the specified criteria.");
            }

            $statusLabel = isset($params['status']) ? ucfirst($params['status']) : 'All';

            // Build markdown response
            $markdown = "## {$statusLabel} Students in \"{$team->name}\"\n\n";
            $markdown .= "Found **{$students->count()}** student".($students->count() !== 1 ? 's' : '').".\n\n";

            // Use table format for larger lists, detailed format for smaller lists
            if ($students->count() > 10) {
                $markdown .= $this->renderStudentsTable($students);
            } else {
                $markdown .= $this->renderStudentsList($students);
            }

            return $this->createTextResponse($markdown);
        } catch (\Exception $e) {
            return $this->createErrorResponse("Error retrieving students: {$e->getMessage()}");
        }
    }

    /**
     * Render students as a markdown table.
     *
     * @param  Collection<int, Student>  $students
     */
    private function renderStudentsTable(Collection $students): string
    {
        $markdown = "| # | Name | Student ID | Email | Status |\n";
        $markdown .= "|---|------|------------|-------|--------|\n";

        foreach ($students as $index => $student) {
            $number = $index + 1;
            $statusEmoji = $this->service->getStatusEmoji($student->status);
            $markdown .= "| {$number} | **{$student->name}** | {$student->student_id} | {$student->email} | {$statusEmoji} {$student->status} |\n";
        }

        return $markdown;
    }

    /**
     * Render students as a detailed markdown list.
     *
     * @param  Collection<int, Student>  $students
     */
    private function renderStudentsList(Collection $students): string
    {
        $markdown = '';

        foreach ($students as $index => $student) {
            $number = $index + 1;
            $statusEmoji = $this->service->getStatusEmoji($student->status);

            $markdown .= "{$number}. **{$student->name}**\n";
            $markdown .= "   - Email: {$student->email}\n";
            $markdown .= "   - Student ID: {$student->student_id}\n";
            $markdown .= "   - Status: {$statusEmoji} {$student->status}\n\n";
        }

        return $markdown;
    }

    /**
     * Alias for getStudentsByStatus - Get all students in the current team.
     *
     * @param  array<string, mixed>  $params
     */
    public function get_current_team_students(array $params): TextResponseItem
    {
        return $this->getStudentsByStatus([]);
    }

    /**
     * Get the name of the current team.
     *
     * @param  array<string, mixed>  $params
     */
    public function get_current_team_name(array $params): TextResponseItem
    {
        try {
            $team = $this->getCurrentTeam();
            if (! $team) {
                return $this->createErrorResponse('No current team found. Please select a team first.');
            }

            return $this->createTextResponse("## Current Team\n\nYour current team is **\"{$team->name}\"**.");
        } catch (\Exception $e) {
            return $this->createErrorResponse("Error retrieving team name: {$e->getMessage()}");
        }
    }

    /**
     * Get detailed information about a specific student.
     *
     * @param  array<string, mixed>  $params
     */
    public function getStudentDetails(array $params): TextResponseItem
    {
        try {
            $team = $this->getCurrentTeam();
            if (! $team) {
                return $this->createErrorResponse('No current team found. Please select a team first.');
            }

            if (! isset($params['studentId'])) {
                return $this->createErrorResponse('Student ID is required.');
            }

            $student = $this->service->getStudentDetails($team, $params['studentId']);

            if (! $student) {
                return $this->createTextResponse("## Student Not Found\n\nNo student found with the specified ID.");
            }

            // Build markdown response
            $markdown = "## Student Profile: {$student->name}\n\n";
            $markdown .= $this->renderStudentBasicInfo($student);

            if ($student->notes) {
                $markdown .= "\n### Notes\n\n";
                $markdown .= "{$student->notes}\n\n";
            }

            $markdown .= $this->renderStudentUserAccount($student);
            $markdown .= $this->renderStudentAcademicProgress($student);

            return $this->createTextResponse($markdown);
        } catch (\Exception $e) {
            return $this->createErrorResponse("Error retrieving student details: {$e->getMessage()}");
        }
    }

    /**
     * Render student basic information as markdown.
     */
    private function renderStudentBasicInfo(Student $student): string
    {
        $markdown = "### Basic Information\n\n";
        $markdown .= "| Field | Value |\n";
        $markdown .= "|-------|-------|\n";
        $markdown .= "| **Name** | {$student->name} |\n";
        $markdown .= "| **Student ID** | {$student->student_id} |\n";
        $markdown .= "| **Email** | {$student->email} |\n";
        $markdown .= '| **Gender** | '.($student->gender ?: 'Not specified')." |\n";
        $markdown .= '| **Birth Date** | '.($student->birth_date ? $student->birth_date->format('Y-m-d') : 'Not specified')." |\n";
        $markdown .= '| **Status** | '.$this->service->getStatusEmoji($student->status)." {$student->status} |\n";

        return $markdown;
    }

    /**
     * Render student user account information as markdown.
     */
    private function renderStudentUserAccount(Student $student): string
    {
        $markdown = "### User Account\n\n";
        if ($student->user_id) {
            $markdown .= "This student is linked to a user account:\n\n";
            $markdown .= "| Field | Value |\n";
            $markdown .= "|-------|-------|\n";
            $markdown .= "| **User ID** | {$student->user->id} |\n";
            $markdown .= "| **User Name** | {$student->user->name} |\n";
            $markdown .= "| **User Email** | {$student->user->email} |\n";
        } else {
            $markdown .= "This student is not linked to any user account.\n\n";
        }

        return $markdown;
    }

    /**
     * Render student academic progress as markdown.
     */
    private function renderStudentAcademicProgress(Student $student): string
    {
        $markdown = "### Academic Progress\n\n";
        $markdown .= "| Category | Count |\n";
        $markdown .= "|----------|------|\n";
        $markdown .= "| **Activity Submissions** | {$student->activitySubmissions->count()} |\n";
        $markdown .= "| **Exam Submissions** | {$student->examSubmissions->count()} |\n";
        $markdown .= "| **Group Assignments** | {$student->groupAssignments->count()} |\n";

        return $markdown;
    }

    /**
     * Get a list of all members in the current team with their roles.
     *
     * @param  array<string, mixed>  $params
     */
    public function getTeamMembers(array $params): TextResponseItem
    {
        try {
            $team = $this->getCurrentTeam();
            if (! $team) {
                return $this->createErrorResponse('No current team found. Please select a team first.');
            }

            $members = $this->service->getTeamMembers($team);

            if ($members->isEmpty()) {
                return $this->createTextResponse("## No Team Members\n\nNo team members found in {$team->name}.");
            }

            // Build markdown table
            $markdown = "## Team Members in {$team->name}\n\n";
            $markdown .= "Found **{$members->count()}** team ".($members->count() !== 1 ? 'members' : 'member').".\n\n";
            $markdown .= "| Name | Email | Role | Status |\n";
            $markdown .= "|------|-------|------|--------|\n";

            foreach ($members as $member) {
                $membership = $member->membership;
                $isOwner = $team->user_id === $member->id;
                $isCurrent = Auth::id() === $member->id;

                $status = [];
                if ($isOwner) {
                    $status[] = '👑 Owner';
                }
                if ($isCurrent) {
                    $status[] = '👤 You';
                }

                $statusText = ! empty($status) ? implode(', ', $status) : '-';

                $markdown .= "| {$member->name} | {$member->email} | {$membership->role} | {$statusText} |\n";
            }

            return $this->createTextResponse($markdown);
        } catch (\Exception $e) {
            return $this->createErrorResponse("Error retrieving team members: {$e->getMessage()}");
        }
    }

    /**
     * Get statistics about the current team.
     *
     * @param  array<string, mixed>  $params
     */
    public function getTeamStatistics(array $params): TextResponseItem
    {
        try {
            $team = $this->getCurrentTeam();
            if (! $team) {
                return $this->createErrorResponse('No current team found. Please select a team first.');
            }

            $statistics = $this->service->getTeamStatistics($team);
            $team = $statistics['team'];
            $studentStatusCounts = $statistics['studentStatusCounts'];

            // Build markdown response
            $markdown = "## Team Statistics: {$team->name}\n\n";

            $markdown .= $this->renderTeamInformation($team);
            $markdown .= $this->renderTeamMemberStatistics($team);
            $markdown .= $this->renderTeamStudentStatistics($team, $studentStatusCounts);
            $markdown .= $this->renderTeamResourceStatistics($team);

            return $this->createTextResponse($markdown);
        } catch (\Exception $e) {
            return $this->createErrorResponse("Error retrieving team statistics: {$e->getMessage()}");
        }
    }

    /**
     * Render team information as markdown.
     */
    private function renderTeamInformation(Team $team): string
    {
        $markdown = "### Team Information\n\n";
        $markdown .= "| Field | Value |\n";
        $markdown .= "|-------|-------|\n";
        $markdown .= "| **Team Name** | {$team->name} |\n";
        $markdown .= "| **Join Code** | `{$team->join_code}` |\n";
        $markdown .= '| **Team Type** | '.($team->personal_team ? 'Personal Team' : 'Collaborative Team')." |\n";
        $markdown .= "| **Team Owner** | {$team->owner->name} ({$team->owner->email}) |\n";

        return $markdown;
    }

    /**
     * Render team member statistics as markdown.
     */
    private function renderTeamMemberStatistics(Team $team): string
    {
        $markdown = "\n### Member Statistics\n\n";
        $markdown .= "| Category | Count |\n";
        $markdown .= "|----------|------|\n";
        $markdown .= "| **Total Team Members** | {$team->users_count} |\n";

        return $markdown;
    }

    /**
     * Render team student statistics as markdown.
     *
     * @param  array<string, int>  $studentStatusCounts
     */
    private function renderTeamStudentStatistics(Team $team, array $studentStatusCounts): string
    {
        $markdown = "\n### Student Statistics\n\n";
        $markdown .= "| Category | Count |\n";
        $markdown .= "|----------|------|\n";
        $markdown .= "| **Total Students** | {$team->students_count} |\n";
        $markdown .= '| **Active Students** | '.($studentStatusCounts[EducationalContextService::STATUS_ACTIVE] ?? 0)." |\n";
        $markdown .= '| **Inactive Students** | '.($studentStatusCounts[EducationalContextService::STATUS_INACTIVE] ?? 0)." |\n";
        $markdown .= '| **Graduated Students** | '.($studentStatusCounts[EducationalContextService::STATUS_GRADUATED] ?? 0)." |\n";

        return $markdown;
    }

    /**
     * Render team resource statistics as markdown.
     */
    private function renderTeamResourceStatistics(Team $team): string
    {
        $markdown = "\n### Educational Resources\n\n";
        $markdown .= "| Category | Count |\n";
        $markdown .= "|----------|------|\n";
        $markdown .= "| **Exams** | {$team->exams_count} |\n";
        $markdown .= "| **Activities** | {$team->activities_count} |\n";
        $markdown .= "| **Resource Categories** | {$team->resource_categories_count} |\n";
        $markdown .= "| **Class Resources** | {$team->class_resources_count} |\n";

        return $markdown;
    }

    /**
     * Get progress information for students.
     *
     * @param  array<string, mixed>  $params
     */
    public function getStudentProgress(array $params): TextResponseItem
    {
        try {
            $team = $this->getCurrentTeam();
            if (! $team) {
                return $this->createErrorResponse('No current team found. Please select a team first.');
            }

            // If student ID is provided, get progress for that student
            if (isset($params['studentId'])) {
                return $this->getIndividualStudentProgress($team, $params['studentId']);
            } else {
                return $this->getAllStudentsProgress($team);
            }
        } catch (\Exception $e) {
            return $this->createErrorResponse("Error retrieving student progress: {$e->getMessage()}");
        }
    }

    /**
     * Get progress for an individual student.
     */
    private function getIndividualStudentProgress(Team $team, string $studentId): TextResponseItem
    {
        $student = $this->service->getStudentProgressById($team, $studentId);

        if (! $student) {
            return $this->createTextResponse("## Student Not Found\n\nNo student found with the specified ID.");
        }

        // Calculate completion percentages
        $activityProgress = $this->service->calculateStudentActivityProgress($student, $team);
        $examProgress = $this->service->calculateStudentExamProgress($student, $team);
        $averageCompletion = ($activityProgress['completion_percentage'] + $examProgress['completion_percentage']) / 2;

        // Build markdown response
        $markdown = "## Progress Report: {$student->name}\n\n";

        $markdown .= $this->renderOverallProgress($averageCompletion);
        $markdown .= $this->renderActivityProgress($activityProgress);
        $markdown .= $this->renderExamProgress($examProgress);

        return $this->createTextResponse($markdown);
    }

    /**
     * Render overall progress as markdown.
     */
    private function renderOverallProgress(float $averageCompletion): string
    {
        $markdown = "### Overall Progress\n\n";
        $markdown .= $this->service->generateProgressBar($averageCompletion)."\n\n";
        $markdown .= "**Overall Completion**: {$this->service->formatPercentage($averageCompletion)}%\n\n";

        return $markdown;
    }

    /**
     * Render activity progress as markdown.
     *
     * @param  array<string, int|float>  $activityProgress
     */
    private function renderActivityProgress(array $activityProgress): string
    {
        $markdown = "### Activity Progress\n\n";
        $markdown .= $this->service->generateProgressBar($activityProgress['completion_percentage'])."\n\n";
        $markdown .= "| Category | Value |\n";
        $markdown .= "|----------|------|\n";
        $markdown .= "| **Completion Rate** | {$this->service->formatPercentage($activityProgress['completion_percentage'])}% |\n";
        $markdown .= "| **Completed Activities** | {$activityProgress['completed_activities']} of {$activityProgress['total_activities']} |\n";
        $markdown .= "| **Pending Activities** | {$activityProgress['pending_activities']} |\n";

        return $markdown;
    }

    /**
     * Render exam progress as markdown.
     *
     * @param  array<string, int|float>  $examProgress
     */
    private function renderExamProgress(array $examProgress): string
    {
        $markdown = "\n### Exam Progress\n\n";
        $markdown .= $this->service->generateProgressBar($examProgress['completion_percentage'])."\n\n";
        $markdown .= "| Category | Value |\n";
        $markdown .= "|----------|------|\n";
        $markdown .= "| **Completion Rate** | {$this->service->formatPercentage($examProgress['completion_percentage'])}% |\n";
        $markdown .= "| **Completed Exams** | {$examProgress['completed_exams']} of {$examProgress['total_exams']} |\n";
        $markdown .= "| **Pending Exams** | {$examProgress['pending_exams']} |\n";

        return $markdown;
    }

    /**
     * Get progress for all students.
     */
    private function getAllStudentsProgress(Team $team): TextResponseItem
    {
        $students = $this->service->getAllStudentsProgress($team);

        if ($students->isEmpty()) {
            return $this->createTextResponse("## No Students\n\nNo students found in the current team.");
        }

        // Build markdown table for all students
        $markdown = "## Student Progress Summary\n\n";
        $markdown .= "Progress overview for **{$students->count()}** students in {$team->name}.\n\n";
        $markdown .= "| Student | Activities | Exams | Overall |\n";
        $markdown .= "|---------|------------|-------|--------|\n";

        foreach ($students as $student) {
            $activityProgress = $this->service->calculateStudentActivityProgress($student, $team);
            $examProgress = $this->service->calculateStudentExamProgress($student, $team);
            $averageCompletion = ($activityProgress['completion_percentage'] + $examProgress['completion_percentage']) / 2;

            $activityPercentage = $this->service->formatPercentage($activityProgress['completion_percentage']);
            $examPercentage = $this->service->formatPercentage($examProgress['completion_percentage']);
            $overallPercentage = $this->service->formatPercentage($averageCompletion);

            $markdown .= "| {$student->name} | {$activityPercentage}% | {$examPercentage}% | {$overallPercentage}% |\n";
        }

        return $this->createTextResponse($markdown);
    }

    /**
     * Get a list of all pending invitations for the current team.
     *
     * @param  array<string, mixed>  $params
     */
    public function getTeamInvitations(array $params): TextResponseItem
    {
        try {
            $team = $this->getCurrentTeam();
            if (! $team) {
                return $this->createErrorResponse('No current team found. Please select a team first.');
            }

            $invitations = $this->service->getTeamInvitations($team);

            if ($invitations->isEmpty()) {
                return $this->createTextResponse("## No Pending Invitations\n\nThere are currently no pending invitations for {$team->name}.");
            }

            // Build markdown table
            $markdown = "## Pending Team Invitations\n\n";
            $markdown .= 'There '.($invitations->count() === 1 ? 'is' : 'are')." **{$invitations->count()}** pending invitation".
                ($invitations->count() !== 1 ? 's' : '')." for {$team->name}.\n\n";
            $markdown .= "| Invited Email | Role | Invitation Date |\n";
            $markdown .= "|--------------|------|----------------|\n";

            foreach ($invitations as $invitation) {
                $createdAt = $invitation->created_at->format('Y-m-d H:i');
                $markdown .= "| {$invitation->email} | {$invitation->role} | {$createdAt} |\n";
            }

            $markdown .= "\n### How to Join\n\n";
            $markdown .= "Invited users can join this team by:\n\n";
            $markdown .= "1. Creating an account with the invited email address (if they don't have one)\n";
            $markdown .= "2. Accepting the invitation from their dashboard\n";
            $markdown .= "3. Alternatively, they can join using the team's join code: `{$team->join_code}`\n";

            return $this->createTextResponse($markdown);
        } catch (\Exception $e) {
            return $this->createErrorResponse("Error retrieving team invitations: {$e->getMessage()}");
        }
    }

    /**
     * Get the current team for the authenticated user.
     */
    private function getCurrentTeam(): ?Team
    {
        $user = Auth::user();

        return $user?->currentTeam;
    }

    /**
     * Create a text response.
     */
    private function createTextResponse(string $text): TextResponseItem
    {
        return new TextResponseItem($text);
    }

    /**
     * Create an error response with standard formatting.
     */
    private function createErrorResponse(string $message): TextResponseItem
    {
        return $this->createTextResponse("## Error\n\n{$message}");
    }
}
