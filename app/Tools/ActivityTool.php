<?php

declare(strict_types=1);

namespace App\Tools;

use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Tool as BaseTool;

class ActivityTool extends BaseTool
{
    public function __construct()
    {
        $this->as('activity_data')
            ->for(
                "Fetches information about the user's class activities, including listings, details, and counts. " .
                "Use ONLY for specific queries like 'list my activities', 'details for activity X', 'how many draft activities?'."
            )
            ->withParameter(
                new EnumSchema(
                    name: 'query_type',
                    description: 'The type of activity data operation.',
                    options: [
                        'list_activities',
                        'get_activity_details',
                        'count_activities',
                    ]
                )
            )
            ->withParameter(
                new StringSchema(
                    name: 'identifier',
                    description: 'Unique identifier (ID or title) for a specific activity. Required for get_activity_details.'
                )
            )
            ->withParameter(
                new ObjectSchema(
                    name: 'filters',
                    description: 'Optional filters for listing/counting activities (e.g., {"status": "published", "term": "midterm", "component_type": "performance_task", "search": "keyword"}).',
                    properties: [
                        new StringSchema(
                            'status',
                            'Filter by status (e.g., published, draft)'
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
                            'Search term for activity title.'
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

        $validFilterKeys = ['status', 'term', 'component_type', 'search'];
        $filters = $filters ? array_intersect_key($filters, array_flip($validFilterKeys)) : [];

        Log::info('ActivityTool invoked', [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'query_type' => $query_type,
            'identifier' => $identifier,
            'filters' => $filters,
        ]);

        if ($query_type === 'get_activity_details' && ! $identifier) {
            return $this->encodeError('The identifier parameter is required for query_type: get_activity_details');
        }

        try {
            $result = match ($query_type) {
                'list_activities' => $this->listActivities($team, $filters),
                'get_activity_details' => $this->getActivityDetails($team, $identifier),
                'count_activities' => $this->countActivities($team, $filters),
                default => ['error' => 'Invalid query_type for ActivityTool: '.$query_type],
            };
        } catch (\Exception $e) {
            Log::error('ActivityTool Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $result = ['error' => 'An internal error occurred: '.$e->getMessage()];
        }

        return $this->limitJsonString(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function listActivities($team, $filters)
    {
        $query = $team->activities()->select(
            'id', 'title', 'component_type', 'term', 'total_points', 'due_date', 'status', 'mode'
        );

        $this->applyActivityFilters($query, $filters);

        $activities = $query->orderBy('due_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        return [
            'activities' => $activities->toArray(),
            'count' => $activities->count(),
            'note' => 'Showing up to 15 activities. Use filters for specific searches.',
        ];
    }

    private function getActivityDetails($team, $identifier)
    {
        $activity = $team->activities()
            ->where(function ($query) use ($identifier): void {
                $query->where('id', $identifier)
                      ->orWhere('title', 'like', "%{$identifier}%");
            })
            ->select(
                'id', 'title', 'description', 'instructions', 'component_type', 'term',
                'total_points', 'due_date', 'status', 'mode', 'allow_late_submissions'
            )
            ->first();

        if (! $activity) {
            return ['error' => 'Activity not found matching identifier: '.$identifier];
        }

        return ['activity' => $activity->toArray()];
    }

    private function countActivities($team, $filters)
    {
        $query = $team->activities();
        $this->applyActivityFilters($query, $filters);
        $count = $query->count();

        $filterDescription = ! empty($filters) ? ' matching filters: '.json_encode($filters) : '';
        return [
            'count' => $count,
            'item_type' => 'activities',
            'description' => "Found {$count} activities{$filterDescription}.",
        ];
    }

    private function applyActivityFilters($query, $filters): void
    {
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
            Log::warning('JSON decoding failed during truncation in ActivityTool', ['error' => $e->getMessage()]);
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