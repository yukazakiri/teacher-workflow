<?php

declare(strict_types=1);

namespace App\Tools;

use App\Models\Exam;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool as BaseTool;

class ExamTool extends BaseTool
{
    public function __construct()
    {
        $this->as('exam_data')
            ->for(
                "Fetches information about the user's class exams, including listings, details, and counts. " .
                "Use ONLY for specific queries like 'list my exams', 'details for exam X', 'how many exams?'."
            )
            ->withParameter(
                new EnumSchema(
                    name: 'query_type',
                    description: 'The type of exam data operation.',
                    options: [
                        'list_exams',
                        'get_exam_details',
                        'count_exams',
                    ]
                )
            )
            ->withParameter(
                new StringSchema(
                    name: 'identifier',
                    description: 'Unique identifier (ID or title) for a specific exam. Required for get_exam_details.'
                )
            )
            ->withParameter(
                new ObjectSchema(
                    name: 'filters',
                    description: 'Optional filters for listing/counting exams (e.g., {"status": "published", "search": "keyword"}).',
                    properties: [
                        new StringSchema(
                            'status',
                            'Filter by status (e.g., published, draft)'
                        ),
                        new StringSchema(
                            'search',
                            'Search term for exam title.'
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

        $validFilterKeys = ['status', 'search'];
        $filters = $filters ? array_intersect_key($filters, array_flip($validFilterKeys)) : [];

        Log::info('ExamTool invoked', [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'query_type' => $query_type,
            'identifier' => $identifier,
            'filters' => $filters,
        ]);

        if ($query_type === 'get_exam_details' && ! $identifier) {
            return $this->encodeError('The identifier parameter is required for query_type: get_exam_details');
        }

        try {
            $result = match ($query_type) {
                'list_exams' => $this->listExams($team, $filters),
                'get_exam_details' => $this->getExamDetails($team, $identifier),
                'count_exams' => $this->countExams($team, $filters),
                default => ['error' => 'Invalid query_type for ExamTool: '.$query_type],
            };
        } catch (\Exception $e) {
            Log::error('ExamTool Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $result = ['error' => 'An internal error occurred: '.$e->getMessage()];
        }

        return $this->limitJsonString(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function listExams($team, $filters)
    {
        $query = $team->exams()->select('id', 'title', 'total_points', 'status', 'created_at');
        $this->applyExamFilters($query, $filters);
        $exams = $query->orderBy('created_at', 'desc')->take(15)->get();

        return [
            'exams' => $exams->toArray(),
            'count' => $exams->count(),
            'note' => 'Showing up to 15 exams.',
        ];
    }

    private function getExamDetails($team, $identifier)
    {
        $exam = $team->exams()
            ->where(function ($query) use ($identifier): void {
                $query->where('id', $identifier)
                      ->orWhere('title', 'like', "%{$identifier}%");
            })
            ->withCount('questions')
            ->select('id', 'title', 'description', 'total_points', 'status')
            ->first();

        if (! $exam) {
            return ['error' => 'Exam not found matching identifier: '.$identifier];
        }

        return ['exam' => $exam->toArray()]; // Includes questions_count
    }

    private function countExams($team, $filters)
    {
        $query = $team->exams();
        $this->applyExamFilters($query, $filters);
        $count = $query->count();

        $filterDescription = ! empty($filters) ? ' matching filters: '.json_encode($filters) : '';
        return [
            'count' => $count,
            'item_type' => 'exams',
            'description' => "Found {$count} exams{$filterDescription}.",
        ];
    }

    private function applyExamFilters($query, $filters): void
    {
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($search = $filters['search'] ?? null) {
            $query->where('title', 'like', "%{$search}%");
        }
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
            Log::warning('JSON decoding failed during truncation in ExamTool', ['error' => $e->getMessage()]);
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