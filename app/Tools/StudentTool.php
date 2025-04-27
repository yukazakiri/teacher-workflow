<?php

declare(strict_types=1);

namespace App\Tools;

use App\Models\Student;
use App\Services\GradingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool as BaseTool;

class StudentTool extends BaseTool
{
    protected GradingService $gradingService;

    public function __construct(GradingService $gradingService)
    {
        $this->gradingService = $gradingService;

        $this->as('student_data')
            ->for(
                "Fetches information about the user's students, including listings, details, summaries, and counts. ".
                "Use ONLY for specific queries like 'list my students', 'details for student X', 'summarize student Y', 'how many active students?'."
            )
            ->withParameter(
                new EnumSchema(
                    name: 'query_type',
                    description: 'The type of student data operation.',
                    options: [
                        'list_students',
                        'get_student_details', // General details
                        'summarize_student', // Detailed summary with grades
                        'count_students',
                    ]
                )
            )
            ->withParameter(
                new StringSchema(
                    name: 'identifier',
                    description: 'Unique identifier (ID, name, or student_id) for a specific student. Required for get_student_details and summarize_student.'
                )
            )
            ->withParameter(
                new ObjectSchema(
                    name: 'filters',
                    description: 'Optional filters for listing/counting students (e.g., {"status": "active", "search": "keyword"}).',
                    properties: [
                        new StringSchema(
                            'status',
                            'Filter by status (e.g., active)'
                        ),
                        new StringSchema(
                            'search',
                            'Search term for student name, email, or student_id.'
                        ),
                    ],
                    requiredFields: []
                )
            )
            ->using($this);
    }

    public function __invoke(
        string $query_type,
        ?string $identifier = null,
        ?array $filters = []
    ): string {
        $user = Auth::user();
        $team = $user?->currentTeam;

        if (! $user || ! $team) {
            return $this->encodeError('User or team context not found.');
        }

        // Basic filter sanitization
        $validFilterKeys = ['status', 'search'];
        $filters = $filters ? array_intersect_key($filters, array_flip($validFilterKeys)) : [];

        Log::info('StudentTool invoked', [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'query_type' => $query_type,
            'identifier' => $identifier,
            'filters' => $filters,
        ]);

        // Check required parameters
        if (in_array($query_type, ['get_student_details', 'summarize_student']) && ! $identifier) {
            return $this->encodeError('The identifier parameter is required for query_type: '.$query_type);
        }

        try {
            $result = match ($query_type) {
                'list_students' => $this->listStudents($team, $filters),
                'get_student_details' => $this->getStudentDetails($team, $identifier, false),
                'summarize_student' => $this->getStudentDetails($team, $identifier, true),
                'count_students' => $this->countStudents($team, $filters),
                default => ['error' => 'Invalid query_type for StudentTool: '.$query_type],
            };
        } catch (\Exception $e) {
            Log::error('StudentTool Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $result = ['error' => 'An internal error occurred: '.$e->getMessage()];
        }

        return $this->limitJsonString(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function listStudents($team, $filters)
    {
        $query = $team
            ->students()
            ->select('id', 'name', 'student_id', 'email', 'status', 'created_at');
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }
        $students = $query->orderBy('name')->take(20)->get();

        return [
            'students' => $students->toArray(),
            'count' => $students->count(),
            'note' => 'Showing up to 20 students. Use filters or identifiers for specific searches.',
        ];
    }

    private function getStudentDetails($team, $identifier, $summarize = false)
    {
        $studentQuery = $team
            ->students()
            ->where(function ($query) use ($identifier): void {
                if (Str::isUuid($identifier)) {
                    $query->where('id', $identifier);
                } else {
                    $query
                        ->where('name', 'like', '%'.$identifier.'%')
                        ->orWhere('student_id', $identifier);
                }
            });

        if ($summarize) {
            $student = $studentQuery
                ->with([
                    'activitySubmissions' => function ($q): void {
                        $q->select('id', 'student_id', 'activity_id', 'score', 'status', 'submitted_at');
                    },
                    'activitySubmissions.activity' => function ($q): void {
                        $q->select('id', 'title', 'total_points', 'credit_units', 'component_type', 'term');
                    },
                    // 'examSubmissions', 'examSubmissions.exam:id,title,total_points' // If exams needed for grade
                ])
                ->first();
        } else {
            $student = $studentQuery
                ->with([
                    'activitySubmissions' => function ($q): void {
                        $q->latest('submitted_at')->limit(5)->with('activity:id,title,total_points,due_date');
                    },
                    'examSubmissions' => function ($q): void {
                        $q->latest('submitted_at')->limit(5)->with('exam:id,title,total_points');
                    },
                ])
                ->select('id', 'name', 'student_id', 'email', 'status', 'birth_date', 'notes', 'created_at')
                ->first();
        }

        if (! $student) {
            return ['error' => 'Student not found matching identifier: '.$identifier];
        }

        if ($summarize) {
            return $this->generateStudentSummary($student, $team);
        } else {
            return $this->formatStudentGeneralDetails($student);
        }
    }

     private function generateStudentSummary($student, $team)
    {
        $summary = "Summary for student: {$student->name} (ID: {$student->student_id}, Status: {$student->status})\n";

        $teamActivities = $team->activities()->where('status', 'published')->get();
        $studentScores = $student->activitySubmissions->pluck('score', 'activity_id');

        $overallGradeValue = null;
        $termGrades = null;
        $calculationDetails = 'No grading system configured or insufficient data.';
        $formattedOverallGrade = 'N/A';
        $numericScale = $team->getCollegeNumericScale();

        try {
            if ($team->usesShsGrading()) {
                $overallGradeValue = $this->gradingService->calculateShsInitialGrade(
                    $studentScores, $teamActivities, $team->shs_ww_weight, $team->shs_pt_weight, $team->shs_qa_weight
                );
                if ($overallGradeValue !== null) {
                    $transmuted = $this->gradingService->transmuteShsGrade($overallGradeValue);
                    $descriptor = $this->gradingService->getShsGradeDescriptor($transmuted);
                    $formattedOverallGrade = "{$transmuted} ({$descriptor})";
                    $calculationDetails = 'SHS System - Initial Grade: '.number_format($overallGradeValue, 2);
                }
            } elseif ($team->usesCollegeTermGrading() && $numericScale) {
                $gradeResult = $this->gradingService->calculateCollegeFinalFinalGrade(
                    $studentScores, $teamActivities, $team->college_prelim_weight, $team->college_midterm_weight, $team->college_final_weight, $numericScale
                );
                $overallGradeValue = $gradeResult['final_grade'];
                $termGrades = $gradeResult['term_grades'];
                if ($overallGradeValue !== null) {
                    $formattedOverallGrade = $this->gradingService->formatCollegeGrade($overallGradeValue, $numericScale);
                    $calculationDetails = "College Term-Based ({$numericScale})";
                }
            } elseif ($team->usesCollegeGwaGrading() && $numericScale) {
                $overallGradeValue = $this->gradingService->calculateCollegeGwa($studentScores, $teamActivities, $numericScale);
                if ($overallGradeValue !== null) {
                    $formattedOverallGrade = $this->gradingService->formatCollegeGrade($overallGradeValue, $numericScale);
                    $calculationDetails = "College GWA-Based ({$numericScale})";
                }
            }
        } catch (\Exception $e) {
            Log::error("Error calculating grade in StudentTool for student {$student->id}: ".$e->getMessage());
            $calculationDetails = 'Error during grade calculation.';
            $formattedOverallGrade = 'Error';
        }

        $summary .= "\n## Overall Grade Analysis ({$calculationDetails})\n";
        $summary .= "- Calculated Overall Grade/Average: **{$formattedOverallGrade}**\n";

        if ($termGrades && $numericScale) {
            $summary .= "- Term Averages:\n";
            foreach ($termGrades as $term => $grade) {
                $formattedTermGrade = $grade !== null ? $this->gradingService->formatCollegeGrade($grade, $numericScale) : 'N/A';
                $summary .= '  - '.ucfirst($term).": {$formattedTermGrade}\n";
            }
        }

        $summary .= "\n## Activity Scores\n";
        if ($teamActivities->isNotEmpty()) {
            $foundScores = false;
            foreach ($teamActivities as $activity) {
                $score = $studentScores[$activity->id] ?? null;
                if ($score !== null) {
                    $summary .= "- {$activity->title}: Score {$score} / {$activity->total_points}\n";
                    $foundScores = true;
                }
            }
            if (! $foundScores) $summary .= "- No graded activity scores found.\n";
        } else {
            $summary .= "- No published activities found.\n";
        }

        $summary .= "\n## Recent Submission Status (Last 5)\n";
        $recentSubs = $student->activitySubmissions()->latest('submitted_at')->limit(5)->with('activity:id,title')->get();
        if ($recentSubs->isNotEmpty()) {
            foreach ($recentSubs as $sub) {
                $activityTitle = $sub->activity?->title ?? 'N/A';
                $scoreInfo = $sub->score !== null ? "Score {$sub->score}" : 'Not Graded';
                $summary .= "- {$activityTitle}: Status {$sub->status} ({$scoreInfo})\n";
            }
        } else {
            $summary .= "- No recent activity submissions found.\n";
        }

        return ['summary' => $summary];
    }

    private function formatStudentGeneralDetails($student)
    {
        return [
            'student' => $student->only(['id', 'name', 'student_id', 'email', 'status', 'birth_date', 'notes', 'created_at']),
            'recent_activity_submissions' => $student->activitySubmissions->map(
                fn ($sub) => $sub->only(['id', 'activity_id', 'status', 'score', 'submitted_at']) + ['activity_title' => $sub->activity?->title]
            )->toArray(),
            'recent_exam_submissions' => $student->examSubmissions->map(
                fn ($sub) => $sub->only(['id', 'exam_id', 'status', 'score', 'submitted_at']) + ['exam_title' => $sub->exam?->title]
            )->toArray(),
        ];
    }


    private function countStudents($team, $filters)
    {
        $query = $team->students();
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }

        $count = $query->count();
        $filterDescription = ! empty($filters) ? ' matching filters: '.json_encode($filters) : '';

        return [
            'count' => $count,
            'item_type' => 'students',
            'description' => "Found {$count} students{$filterDescription}.",
        ];
    }

    private function encodeError(string $message): string
    {
        return json_encode(['error' => $message]);
    }

    private function limitJsonString(string $jsonString, int $maxLength = 8000): string
    {
         if (mb_strlen($jsonString) <= $maxLength) {
             return $jsonString;
         }

         // Attempt to truncate intelligently (simple version)
         try {
             $data = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
             $truncated = false;

             $walkAndTruncate = function (&$item) use (&$walkAndTruncate, &$truncated): void {
                 if (is_array($item)) {
                     if (array_keys($item) === range(0, count($item) - 1) && count($item) > 5) {
                         $item = array_slice($item, 0, 5);
                         $item[] = '... (truncated)';
                         $truncated = true;
                     } else {
                         foreach ($item as &$value) $walkAndTruncate($value); unset($value);
                     }
                 }
             };
             $walkAndTruncate($data);

             if ($truncated) {
                 $truncatedJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                 if (mb_strlen($truncatedJson) <= $maxLength) return $truncatedJson;
             }
         } catch (\JsonException $e) {
            Log::warning('JSON decoding failed during truncation in StudentTool', ['error' => $e->getMessage()]);
         }

         // Fallback: Hard truncate
         $cutPosition = max(
            strrpos(substr($jsonString, 0, $maxLength - 50), ','),
            strrpos(substr($jsonString, 0, $maxLength - 50), '}'),
            strrpos(substr($jsonString, 0, $maxLength - 50), ']')
         );

         if ($cutPosition > $maxLength / 2) {
             return substr($jsonString, 0, $cutPosition) . "\n... // TRUNCATED DUE TO LENGTH\n}";
         } else {
             return mb_substr($jsonString, 0, $maxLength - 20) . '... [TRUNCATED]';
         }
    }
} 