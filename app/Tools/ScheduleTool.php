<?php

declare(strict_types=1);

namespace App\Tools;

use App\Models\ScheduleItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Tool;
use Prism\Prism\Tool as BaseTool;

class ScheduleTool extends BaseTool
{
    public function __construct()
    {
        $this->as('schedule_data')
            ->for("Fetches the user's weekly schedule.")
            // This tool doesn't require parameters, so we omit `withParameter`
            ->using($this);
    }

    public function __invoke(): string
    {
        $user = Auth::user();
        $team = $user?->currentTeam;

        if (! $user || ! $team) {
            return $this->encodeError('User or team context not found.');
        }

        Log::info('ScheduleTool invoked', [
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        try {
            $result = $this->getSchedule($team);
        } catch (\Exception $e) {
            Log::error('ScheduleTool Error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $result = ['error' => 'An internal error occurred: '.$e->getMessage()];
        }

        return $this->limitJsonString(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function getSchedule($team)
    {
        $items = ScheduleItem::where('team_id', $team->id)
            ->select('day_of_week', 'start_time', 'end_time', 'title', 'location', 'color')
            ->orderBy('start_time')
            ->get();

        if ($items->isEmpty()) {
            return ['message' => 'No schedule items found for this team.'];
        }

        $groupedByDay = $items->groupBy('day_of_week');
        $daysOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $formattedSchedule = [];

        foreach ($daysOrder as $day) {
            if ($groupedByDay->has($day)) {
                $formattedItemsForDay = $groupedByDay->get($day)->map(function ($item) {
                    $startTime = \Carbon\Carbon::parse($item->start_time);
                    $endTime = \Carbon\Carbon::parse($item->end_time);
                    return [
                        'time' => $startTime->format('g:i A').' - '.$endTime->format('g:i A'),
                        'title' => $item->title,
                        'location' => $item->location,
                        'color' => $item->color,
                    ];
                })->toArray();
                $formattedSchedule[$day] = $formattedItemsForDay;
            }
        }

        return ['weekly_schedule' => $formattedSchedule];
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
            Log::warning('JSON decoding failed during truncation in ScheduleTool', ['error' => $e->getMessage()]);
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