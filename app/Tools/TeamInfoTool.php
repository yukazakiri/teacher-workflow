<?php

declare(strict_types=1);

namespace App\Tools;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Tool as BaseTool;

class TeamInfoTool extends BaseTool
{
    public function __construct()
    {
        $this->as('user_team_info')
            ->for(
                "Fetches information about the current user and their team (including grading system details). " .
                "Use ONLY for queries like 'what is my user info?', 'show team details', 'what grading system are we using?'."
            )
            ->withParameter(
                new EnumSchema(
                    name: 'info_type',
                    description: 'The type of information to fetch.',
                    options: [
                        'get_team_info',
                        'get_user_info',
                    ]
                )
            )
            ->using($this);
    }

    public function __invoke(string $info_type): string
    {
        $user = Auth::user();
        $team = $user?->currentTeam;

        if (! $user) {
            return $this->encodeError('User context not found.');
        }
        if ($info_type === 'get_team_info' && ! $team) {
            return $this->encodeError('Team context not found, cannot get team info.');
        }

        Log::info('TeamInfoTool invoked', [
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'info_type' => $info_type,
        ]);

        try {
            $result = match ($info_type) {
                'get_team_info' => $this->getTeamInfo($team),
                'get_user_info' => $this->getUserInfo($user),
                default => ['error' => 'Invalid info_type for TeamInfoTool: '.$info_type],
            };
        } catch (\Exception $e) {
            Log::error('TeamInfoTool Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $result = ['error' => 'An internal error occurred: '.$e->getMessage()];
        }

        return $this->limitJsonString(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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

    private function getTeamInfo($team)
    {
        $team->loadCount('students', 'activities', 'exams');
        $team->load('owner:id,name,email');

        $members = $team->allUsers()->map(function ($user) use ($team) {
            $roleObject = $user->teamRole($team);
            $role = $roleObject ? $roleObject->key : 'member';
            return ['name' => $user->name, 'role' => $role];
        });

        return [
            'team_info' => [
                'id' => $team->id,
                'name' => $team->name,
                'owner' => $team->owner?->name,
                'grading_system_description' => $team->gradingSystemDescription, // Accessor
                'grading_details' => $this->getGradingDetails($team),
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
                    'Written Work (WW)' => $team->shs_ww_weight,
                    'Performance Task (PT)' => $team->shs_pt_weight,
                    'Quarterly Assessment (QA)' => $team->shs_qa_weight,
                ],
            ];
        } elseif ($team->usesCollegeGrading()) {
            $details = [
                'type' => 'College',
                'scale_code' => $team->college_grading_scale,
                'numeric_scale_type' => $team->getCollegeNumericScale(),
            ];
            if ($team->usesCollegeTermGrading()) {
                $details['basis'] = 'Term-Based';
                $details['weights'] = [
                    'Prelim' => $team->college_prelim_weight,
                    'Midterm' => $team->college_midterm_weight,
                    'Final' => $team->college_final_weight,
                ];
            } elseif ($team->usesCollegeGwaGrading()) {
                $details['basis'] = 'GWA-Based';
                $details['note'] = 'Grades calculated based on activity credit units and scores.';
            }
            return $details;
        }
        return ['type' => 'Not Configured'];
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

             $walkAndTruncate = function (&$item) use (&$walkAndTruncate, &$truncated) {
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
            Log::warning('JSON decoding failed during truncation in TeamInfoTool', ['error' => $e->getMessage()]);
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