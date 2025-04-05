<?php

// teacher-workflow/app/Tools/DataAccessTool.php

namespace App\Tools;

use App\Models\Activity;
use App\Models\Exam;
use App\Models\ScheduleItem;
use App\Models\Student;
use App\Services\GradingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema; // Alias the base Tool class
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool as BaseTool;

class DataAccessTool extends BaseTool
{
    protected GradingService $gradingService;

    // Inject GradingService via the service container
    public function __construct()
    {
        // Resolve GradingService from the container
        $this->gradingService = app(GradingService::class);

        $this->as('data_access')
            ->for(
                "Fetches information about the user's students, activities, exams, schedule, and team data. ".
                    'Use this tool ONLY when the user explicitly asks questions about THEIR specific data, such as '.
                    "'list my students', 'how many active students?', 'details about activity X', 'show my schedule', ".
                    "'what's my team's grading system?', 'summarize John Doe's recent performance', 'calculate Jane Doe's average grade', etc. ". // Added example
                    'Do NOT use this for general knowledge questions.'
            )
            ->withParameter(
                new EnumSchema(
                    name: 'query_type',
                    description: 'The type of data to fetch.',
                    options: [
                        'list_students',
                        'get_student_details', // General details
                        'list_activities',
                        'get_activity_details',
                        'list_exams',
                        'get_exam_details',
                        'get_schedule',
                        'get_team_info',
                        'get_user_info',
                        'summarize_student', // More detailed summary including calculated average
                        'count_items',
                    ]
                )
            )
            ->withParameter(
                new StringSchema(
                    name: 'identifier',
                    description: 'Unique identifier (ID, name, or student_id) for specific items (e.g., student name, activity title, exam ID). Required by LLM context for get_*_details and summarize_student.'
                )
            )
            ->withParameter(
                new ObjectSchema(
                    name: 'filters',
                    description: 'Optional filters to apply (e.g., {"status": "active", "term": "prelim"}). Keys depend on query_type.',
                    properties: [
                        new StringSchema(
                            'status',
                            'Filter by status (e.g., active, published, draft)'
                        ),
                        new StringSchema(
                            'term',
                            'Filter activities by term (prelim, midterm, final)'
                        ),
                        new StringSchema(
                            'component_type',
                            'Filter activities by SHS component (written_work, performance_task, quarterly_assessment)'
                        ),
                        new StringSchema(
                            'search',
                            'Generic search term for names or titles'
                        ),
                    ],
                    requiredFields: []
                )
            )
            ->withParameter(
                new StringSchema(
                    name: 'item_type_for_count',
                    description: 'Specify item type for count_items query (e.g., "students", "activities"). Required by LLM context for count_items.'
                )
            )
            ->using($this); // Use this class's __invoke method
    }

    public function __invoke(
        string $query_type,
        ?string $identifier = null,
        ?array $filters = [], // Changed from object to array for easier handling
        ?string $item_type_for_count = null
    ): string {
        $user = Auth::user();
        $team = $user?->currentTeam;

        if (! $user || ! $team) {
            // Return JSON directly as the tool output must be a string
            return json_encode([
                'error' => 'User or team context not found. Cannot access data.',
            ]);
        }

        // Basic filter sanitization/validation (can be expanded)
        $validFilterKeys = ['status', 'term', 'component_type', 'search'];
        $filters = $filters
            ? array_intersect_key($filters, array_flip($validFilterKeys))
            : [];
        // $search = $filters['search'] ?? null; // Search handled within specific methods

        Log::info('DataAccessTool invoked', [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'query_type' => $query_type,
            'identifier' => $identifier,
            'filters' => $filters,
            'item_type_for_count' => $item_type_for_count,
        ]);

        // Check required parameters based on query_type (important since we removed schema-level required)
        if ($query_type === 'count_items' && ! $item_type_for_count) {
            return json_encode([
                'error' => 'The item_type_for_count parameter is required when query_type is count_items.',
            ]);
        }
        if (
            in_array($query_type, [
                'get_student_details',
                'summarize_student',
                'get_activity_details',
                'get_exam_details',
            ]) &&
            ! $identifier
        ) {
            return json_encode([
                'error' => 'The identifier parameter is required for query_type: '.
                    $query_type,
            ]);
        }

        try {
            $result = match ($query_type) {
                'list_students' => $this->listStudents($team, $filters),
                'get_student_details',
                'summarize_student' => $this->getStudentDetails(
                    $team,
                    $identifier,
                    $query_type === 'summarize_student'
                ),
                'list_activities' => $this->listActivities($team, $filters),
                'get_activity_details' => $this->getActivityDetails(
                    $team,
                    $identifier
                ),
                'list_exams' => $this->listExams($team, $filters),
                'get_exam_details' => $this->getExamDetails($team, $identifier),
                'get_schedule' => $this->getSchedule($team),
                'get_team_info' => $this->getTeamInfo($team),
                'get_user_info' => $this->getUserInfo($user),
                'count_items' => $this->countItems(
                    $team,
                    $item_type_for_count,
                    $filters
                ),
                default => [
                    'error' => 'Invalid query_type specified: '.$query_type,
                ],
            };
        } catch (\Exception $e) {
            Log::error('DataAccessTool Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $result = [
                'error' => 'An internal error occurred while fetching data: '.
                    $e->getMessage(),
            ];
        }

        // Limit response size - crucial for LLMs
        return $this->limitJsonString(
            json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        ); // Added flags
    }
    // --- Helper methods for data fetching ---

    private function listStudents($team, $filters)
    {
        $query = $team
            ->students()
            ->select(
                'id',
                'name',
                'student_id',
                'email',
                'status',
                'created_at'
            );
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");
            });
        }
        $students = $query->orderBy('name')->take(20)->get(); // Limit results

        return [
            'students' => $students->toArray(),
            'count' => $students->count(),
            'note' => 'Showing up to 20 students. Use filters or identifiers for specific searches.',
        ];
    }

    private function getStudentDetails($team, $identifier, $summarize = false)
    {
        // $identifier is already checked in __invoke

        // Build the base query, correcting the where clause
        $studentQuery = $team
            ->students()
            ->where(function ($query) use ($identifier) {
                // Check if identifier is a UUID for the 'id' column
                if (Str::isUuid($identifier)) {
                    $query->where('id', $identifier);
                    // If student_id can also be a UUID, uncomment the next line
                    // $query->orWhere('student_id', $identifier);
                } else {
                    // Otherwise, search by name (LIKE) or student_id (exact match)
                    $query
                        ->where('name', 'like', '%'.$identifier.'%')
                        ->orWhere('student_id', $identifier);
                }
                // If 'id' could also be non-UUID (unlikely), add this:
                // $query->orWhere('id', $identifier);
            });

        // Eager load different data based on whether we are summarizing
        if ($summarize) {
            // Load ALL submissions + related activity/exam data needed for grading
            $student = $studentQuery
                ->with([
                    // Load ALL submissions, but select necessary fields
                    'activitySubmissions' => function ($q) {
                        $q->select(
                            'id',
                            'student_id',
                            'activity_id',
                            'score',
                            'status',
                            'submitted_at'
                        );
                    },
                    // Load related activities (needed for total points, units, term, component)
                    // We could load this separately but loading via student ensures we only get relevant ones if needed later
                    'activitySubmissions.activity' => function ($q) {
                        $q->select(
                            'id',
                            'title',
                            'total_points',
                            'credit_units',
                            'component_type',
                            'term'
                        );
                    },
                    // Add exam submissions if they contribute to overall grade (adjust if needed)
                    // 'examSubmissions', 'examSubmissions.exam:id,title,total_points'
                ])
                ->first();
        } else {
            // Load only recent submissions for a general overview
            $student = $studentQuery
                ->with([
                    'activitySubmissions' => function ($q) {
                        $q->latest('submitted_at')
                            ->limit(5)
                            ->with('activity:id,title,total_points,due_date');
                    },
                    'examSubmissions' => function ($q) {
                        $q->latest('submitted_at')
                            ->limit(5)
                            ->with('exam:id,title,total_points');
                    },
                ])
                ->select(
                    'id',
                    'name',
                    'student_id',
                    'email',
                    'status',
                    'birth_date',
                    'notes',
                    'created_at'
                ) // Select specific student columns
                ->first();
        }

        if (! $student) {
            return [
                'error' => 'Student not found matching identifier: '.$identifier,
            ];
        }

        if ($summarize) {
            // --- Enhanced Summary Logic ---
            $summary = "Summary for student: {$student->name} (ID: {$student->student_id}, Status: {$student->status})\n";

            // 1. Get relevant activities (all published for the team)
            $teamActivities = $team
                ->activities()
                ->where('status', 'published')
                ->get();

            // 2. Prepare scores in the format needed by GradingService
            $studentScores = $student->activitySubmissions->pluck(
                'score',
                'activity_id'
            ); // ['activity_id' => score]

            // 3. Calculate Overall Grade using GradingService
            $overallGradeValue = null;
            $termGrades = null; // For college term-based
            $calculationDetails =
                'No grading system configured or insufficient data.'; // Default message
            $formattedOverallGrade = 'N/A';
            $numericScale = $team->getCollegeNumericScale(); // Get '5_point', '4_point', etc.

            try {
                if ($team->usesShsGrading()) {
                    $overallGradeValue = $this->gradingService->calculateShsInitialGrade(
                        $studentScores,
                        $teamActivities, // Pass all team activities
                        $team->shs_ww_weight,
                        $team->shs_pt_weight,
                        $team->shs_qa_weight
                    );
                    if ($overallGradeValue !== null) {
                        $transmuted = $this->gradingService->transmuteShsGrade(
                            $overallGradeValue
                        );
                        $descriptor = $this->gradingService->getShsGradeDescriptor(
                            $transmuted
                        );
                        $formattedOverallGrade = "{$transmuted} ({$descriptor})";
                        $calculationDetails =
                            'SHS System - Initial Grade: '.
                            number_format($overallGradeValue, 2);
                    }
                } elseif ($team->usesCollegeTermGrading() && $numericScale) {
                    $gradeResult = $this->gradingService->calculateCollegeFinalFinalGrade(
                        $studentScores,
                        $teamActivities,
                        $team->college_prelim_weight,
                        $team->college_midterm_weight,
                        $team->college_final_weight,
                        $numericScale
                    );
                    $overallGradeValue = $gradeResult['final_grade'];
                    $termGrades = $gradeResult['term_grades']; // Store term grades
                    if ($overallGradeValue !== null) {
                        $formattedOverallGrade = $this->gradingService->formatCollegeGrade(
                            $overallGradeValue,
                            $numericScale
                        );
                        $calculationDetails = "College Term-Based ({$numericScale})";
                    }
                } elseif ($team->usesCollegeGwaGrading() && $numericScale) {
                    $overallGradeValue = $this->gradingService->calculateCollegeGwa(
                        $studentScores,
                        $teamActivities,
                        $numericScale
                    );
                    if ($overallGradeValue !== null) {
                        $formattedOverallGrade = $this->gradingService->formatCollegeGrade(
                            $overallGradeValue,
                            $numericScale
                        );
                        $calculationDetails = "College GWA-Based ({$numericScale})";
                    }
                }
            } catch (\Exception $e) {
                Log::error(
                    "Error calculating grade in DataAccessTool for student {$student->id}: ".
                        $e->getMessage()
                );
                $calculationDetails = 'Error during grade calculation.';
                $formattedOverallGrade = 'Error';
            }

            $summary .= "\n## Overall Grade Analysis ({$calculationDetails})\n";
            $summary .= "- Calculated Overall Grade/Average: **{$formattedOverallGrade}**\n";

            // Include term grades if applicable
            if ($termGrades && $numericScale) {
                $summary .= "- Term Averages:\n";
                foreach ($termGrades as $term => $grade) {
                    $formattedTermGrade =
                        $grade !== null
                            ? $this->gradingService->formatCollegeGrade(
                                $grade,
                                $numericScale
                            )
                            : 'N/A';
                    $summary .=
                        '  - '.ucfirst($term).": {$formattedTermGrade}\n";
                }
            }

            // 4. List Activity Scores used in calculation
            $summary .= "\n## Activity Scores\n";
            if ($teamActivities->isNotEmpty()) {
                $foundScores = false;
                foreach ($teamActivities as $activity) {
                    $score = $studentScores[$activity->id] ?? null;
                    if ($score !== null) {
                        $summary .= "- {$activity->title}: Score {$score} / {$activity->total_points}\n";
                        $foundScores = true;
                    }
                    // Optionally list activities with missing scores
                    // else { $summary .= "- {$activity->title}: Score Missing\n"; }
                }
                if (! $foundScores) {
                    $summary .=
                        "- No graded activity scores found for this student.\n";
                }
            } else {
                $summary .=
                    "- No published activities found for the team to calculate scores from.\n";
            }

            // 5. Include recent submission status (optional, reuse limited data)
            $summary .= "\n## Recent Submission Status (Last 5)\n";
            $recentSubs = $student
                ->activitySubmissions()
                ->latest('submitted_at')
                ->limit(5)
                ->with('activity:id,title')
                ->get();
            if ($recentSubs->isNotEmpty()) {
                foreach ($recentSubs as $sub) {
                    $activityTitle = $sub->activity?->title ?? 'N/A';
                    $scoreInfo =
                        $sub->score !== null
                            ? "Score {$sub->score}"
                            : 'Not Graded';
                    $summary .= "- {$activityTitle}: Status {$sub->status} ({$scoreInfo})\n";
                }
            } else {
                $summary .= "- No recent activity submissions found.\n";
            }

            // Return the summary string
            return ['summary' => $summary];
        } else {
            // --- Original General Details ---
            return [
                'student' => $student->only([
                    'id',
                    'name',
                    'student_id',
                    'email',
                    'status',
                    'birth_date',
                    'notes',
                    'created_at',
                ]),
                'recent_activity_submissions' => $student->activitySubmissions
                    ->map(
                        fn ($sub) => $sub->only([
                            'id',
                            'activity_id',
                            'status',
                            'score',
                            'submitted_at',
                        ]) + ['activity_title' => $sub->activity?->title]
                    )
                    ->toArray(),
                'recent_exam_submissions' => $student->examSubmissions
                    ->map(
                        fn ($sub) => $sub->only([
                            'id',
                            'exam_id',
                            'status',
                            'score',
                            'submitted_at',
                        ]) + ['exam_title' => $sub->exam?->title]
                    )
                    ->toArray(),
            ];
        }
    }

    private function listActivities($team, $filters)
    {
        $query = $team
            ->activities()
            ->select(
                'id',
                'title',
                'component_type',
                'term',
                'total_points',
                'due_date',
                'status',
                'mode'
            );
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($term = $filters['term'] ?? null) {
            $query->where('term', $term);
        }
        if ($component = $filters['component_type'] ?? null) {
            $query->where('component_type', $component);
        }
        if ($search = $filters['search'] ?? null) {
            $query->where('title', 'like', "%{$search}%");
        }
        $activities = $query
            ->orderBy('due_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get(); // Limit results

        return [
            'activities' => $activities->toArray(),
            'count' => $activities->count(),
            'note' => 'Showing up to 15 activities. Use filters for specific searches.',
        ];
    }

    private function getActivityDetails($team, $identifier)
    {
        // $identifier is already checked in __invoke
        $activity = $team
            ->activities()
            ->where(function ($query) use ($identifier) {
                $query
                    ->where('id', $identifier)
                    ->orWhere('title', 'like', "%{$identifier}%");
            })
            ->select(
                'id',
                'title',
                'description',
                'instructions',
                'component_type',
                'term',
                'total_points',
                'due_date',
                'status',
                'mode',
                'allow_late_submissions'
            )
            ->first();

        if (! $activity) {
            return [
                'error' => 'Activity not found matching identifier: '.$identifier,
            ];
        }

        return ['activity' => $activity->toArray()];
    }

    private function listExams($team, $filters)
    {
        $query = $team
            ->exams()
            ->select('id', 'title', 'total_points', 'status', 'created_at');
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($search = $filters['search'] ?? null) {
            $query->where('title', 'like', "%{$search}%");
        }
        $exams = $query->orderBy('created_at', 'desc')->take(15)->get();

        return [
            'exams' => $exams->toArray(),
            'count' => $exams->count(),
            'note' => 'Showing up to 15 exams.',
        ];
    }

    private function getExamDetails($team, $identifier)
    {
        // $identifier is already checked in __invoke
        $exam = $team
            ->exams()
            ->where(function ($query) use ($identifier) {
                $query
                    ->where('id', $identifier)
                    ->orWhere('title', 'like', "%{$identifier}%");
            })
            ->withCount('questions')
            ->select('id', 'title', 'description', 'total_points', 'status')
            ->first();

        if (! $exam) {
            return [
                'error' => 'Exam not found matching identifier: '.$identifier,
            ];
        }

        return ['exam' => $exam->toArray()]; // Includes questions_count
    }

    private function getSchedule($team)
    {
        // Fetch all schedule items directly for the team
        $items = ScheduleItem::where('team_id', $team->id)
            // Fetch necessary columns
            ->select(
                'day_of_week',
                'start_time',
                'end_time',
                'title',
                'location',
                'color'
            ) // Added color
            // Order primarily by start time for processing within days
            ->orderBy('start_time')
            ->get();

        if ($items->isEmpty()) {
            return ['message' => 'No schedule items found for this team.'];
        }

        // Group items by day_of_week
        $groupedByDay = $items->groupBy('day_of_week');

        // Define the desired order of days
        $daysOrder = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ];

        $formattedSchedule = [];

        // Iterate through the desired day order to ensure consistent sorting
        foreach ($daysOrder as $day) {
            if ($groupedByDay->has($day)) {
                // Format the items for the current day
                // Ensure start_time and end_time are Carbon instances for formatting
                $formattedItemsForDay = $groupedByDay
                    ->get($day)
                    ->map(function ($item) {
                        // Ensure times are Carbon instances if they aren't already (depends on casts)
                        $startTime = \Carbon\Carbon::parse($item->start_time);
                        $endTime = \Carbon\Carbon::parse($item->end_time);

                        return [
                            // 'day' => $item->day_of_week, // Day is already the key
                            'time' => $startTime->format('g:i A').
                                ' - '.
                                $endTime->format('g:i A'), // Use AM/PM format
                            'title' => $item->title,
                            'location' => $item->location,
                            'color' => $item->color, // Include color
                        ];
                    })
                    ->toArray(); // Convert the collection of items for the day to an array

                // Add the formatted items under the day key
                $formattedSchedule[$day] = $formattedItemsForDay;
            }
        }

        // Return the schedule grouped and ordered by day
        // Use a descriptive key for the AI
        return ['weekly_schedule' => $formattedSchedule];
    }

    private function getTeamInfo($team)
    {
        $team->loadCount('students', 'activities', 'exams');
        $team->load('owner:id,name,email'); // Load owner info selectively

        $members = $team->allUsers()->map(function ($user) use ($team) {
            // Ensure teamRole returns an object or null
            $roleObject = $user->teamRole($team);
            $role = $roleObject ? $roleObject->key : 'member'; // Access key property

            return ['name' => $user->name, 'role' => $role];
        });

        return [
            'team_info' => [
                'id' => $team->id,
                'name' => $team->name,
                'owner' => $team->owner?->name,
                'grading_system_description' => $team->gradingSystemDescription, // Use the accessor
                'grading_details' => $this->getGradingDetails($team),
                // 'join_code' => $team->join_code, // Decide if exposing this is safe/needed
                'student_count' => $team->students_count,
                'activity_count' => $team->activities_count,
                'exam_count' => $team->exams_count,
                'created_at' => optional($team->created_at)->toIso8601String(),
            ],
            'team_members' => $members->toArray(),
        ];
    }

    private function getGradingDetails($team)
    {
        if ($team->usesShsGrading()) {
            return [
                'type' => 'SHS',
                'weights' => [
                    'Written Work (WW)' => $team->shs_ww_weight, // Return numbers
                    'Performance Task (PT)' => $team->shs_pt_weight,
                    'Quarterly Assessment (QA)' => $team->shs_qa_weight,
                ],
            ];
        } elseif ($team->usesCollegeGrading()) {
            $details = [
                'type' => 'College',
                'scale_code' => $team->college_grading_scale, // e.g., term_5_point
                'numeric_scale_type' => $team->getCollegeNumericScale(), // e.g., 5_point
            ];
            if ($team->usesCollegeTermGrading()) {
                $details['basis'] = 'Term-Based';
                $details['weights'] = [
                    'Prelim' => $team->college_prelim_weight, // Return numbers
                    'Midterm' => $team->college_midterm_weight,
                    'Final' => $team->college_final_weight,
                ];
            } elseif ($team->usesCollegeGwaGrading()) {
                $details['basis'] = 'GWA-Based';
                $details['note'] =
                    'Grades typically calculated based on activity credit units and scores.';
            }

            return $details;
        }

        return ['type' => 'Not Configured'];
    }

    private function getUserInfo($user)
    {
        return [
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'current_team_id' => $user->current_team_id,
                'teams_count' => $user->allTeams()->count(),
            ],
        ];
    }

    private function countItems($team, $itemType, $filters)
    {
        // $itemType is already checked in __invoke

        $query = null;
        switch (strtolower($itemType)) {
            case 'students':
                $query = $team->students();
                if ($status = $filters['status'] ?? null) {
                    $query->where('status', $status);
                }
                break;
            case 'activities':
                $query = $team->activities();
                if ($status = $filters['status'] ?? null) {
                    $query->where('status', $status);
                }
                if ($term = $filters['term'] ?? null) {
                    $query->where('term', $term);
                }
                if ($component = $filters['component_type'] ?? null) {
                    $query->where('component_type', $component);
                }
                break;
            case 'exams':
                $query = $team->exams();
                if ($status = $filters['status'] ?? null) {
                    $query->where('status', $status);
                }
                break;
            default:
                return [
                    'error' => 'Unsupported item_type_for_count: '.$itemType,
                ];
        }

        if ($search = $filters['search'] ?? null) {
            // Add basic search capability for count if applicable
            if (
                in_array(strtolower($itemType), [
                    'students',
                    'activities',
                    'exams',
                ])
            ) {
                // Determine column based on type, default to 'name' or 'title'
                $searchColumn = match (strtolower($itemType)) {
                    'students' => 'name',
                    'activities', 'exams' => 'title',
                    default => null, // Should not happen based on switch
                };
                if ($searchColumn) {
                    // Apply search more broadly for students
                    if (strtolower($itemType) === 'students') {
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('student_id', 'like', "%{$search}%");
                        });
                    } else {
                        $query->where($searchColumn, 'like', "%{$search}%");
                    }
                }
            }
        }

        $count = $query->count();
        $filterDescription = ! empty($filters)
            ? ' matching filters: '.json_encode($filters)
            : '';

        return [
            'count' => $count,
            'item_type' => $itemType,
            'description' => "Found {$count} {$itemType}{$filterDescription}.",
        ];
    }

    /**
     * Limits the size of the JSON string to prevent exceeding LLM context limits.
     * Ensures the output is valid JSON even after truncation.
     */
    private function limitJsonString(
        string $jsonString,
        int $maxLength = 8000
    ): string {
        if (mb_strlen($jsonString) <= $maxLength) {
            return $jsonString;
        }

        // Attempt to truncate intelligently by shortening arrays within the structure
        try {
            $data = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
            $truncated = false;

            // Recursively walk through the data and truncate long arrays
            $walkAndTruncate = function (&$item) use (
                &$walkAndTruncate,
                &$truncated
            ) {
                if (is_array($item)) {
                    // Check if it's a list (sequential numeric keys)
                    if (array_keys($item) === range(0, count($item) - 1)) {
                        if (count($item) > 5) {
                            // Truncate lists longer than 5 items
                            $item = array_slice($item, 0, 5);
                            // Add a marker without creating a complex object
                            $item[] = '... (truncated)';
                            $truncated = true;
                        }
                    } else {
                        // If it's an associative array, recurse
                        foreach ($item as &$value) {
                            $walkAndTruncate($value);
                        }
                        unset($value); // Unset reference
                    }
                }
            };

            $walkAndTruncate($data);

            if ($truncated) {
                // Re-encode the truncated data
                $truncatedJson = json_encode(
                    $data,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                );
                if (mb_strlen($truncatedJson) <= $maxLength) {
                    return $truncatedJson;
                }
            }
        } catch (\JsonException $e) {
            // If decoding failed, fall back to string truncation
            Log::warning('JSON decoding failed during truncation', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: Hard truncate the string if smart truncation failed or wasn't enough
        // Try to find the last valid JSON delimiter before the limit
        $lastComma = strrpos(substr($jsonString, 0, $maxLength - 50), ',');
        $lastBrace = strrpos(substr($jsonString, 0, $maxLength - 50), '}');
        $lastBracket = strrpos(substr($jsonString, 0, $maxLength - 50), ']');

        $cutPosition = max($lastComma ?: 0, $lastBrace ?: 0, $lastBracket ?: 0);

        if ($cutPosition > $maxLength / 2) {
            // Only use cut position if it's reasonably far in
            return substr($jsonString, 0, $cutPosition).
                "\n... // TRUNCATED DUE TO LENGTH\n}"; // Attempt to close structure
        } else {
            // If no good cut point found, just hard cut
            return mb_substr($jsonString, 0, $maxLength - 20).
                '... [TRUNCATED]';
        }
    }
}
