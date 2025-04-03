<?php
namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\ActivityResource; // Import ActivityResource
use App\Filament\Pages\Dashboard; // Import Activity Resource
use App\Models\Team; // Import Team
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // For logging

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected static string $view = "filament.resources.student-resource.pages.list-students";

    // Define constants needed (or import from Dashboard)
    const ONBOARDING_STUDENT_THRESHOLD = 5;

    public int $onboardingState = 0; // 0 = none, 2 = show create activity modal
    public bool $showCreateActivityModal = false;
    public ?string $activityResourceCreateUrl = null;
    public int $studentThreshold = 0;

    public function mount(): void
    {
        parent::mount(); // Call parent mount method

        $user = Auth::user();
        $team = $user?->currentTeam()->withCount("students")->first(); // Eager load count

        if ($team) {
            $currentStep = (int) $team->onboarding_step;
            $studentCount = $team->students_count; // Use eager loaded count
            $this->studentThreshold = Dashboard::ONBOARDING_STUDENT_THRESHOLD; // Get threshold

            // Check conditions for showing the 'Create Activity' modal (State 2)
            if ($currentStep <= 1 && $studentCount >= $this->studentThreshold) {
                $this->showCreateActivityModal = true;
                $this->activityResourceCreateUrl = ActivityResource::getUrl(
                    "create-guide",
                    ["tenant" => $team]
                );
                Log::info(
                    "Student List: Conditions met for onboarding step 2 modal.",
                    ["team_id" => $team->id, "user_id" => $user->id]
                );
            } else {
                Log::debug(
                    "Student List: Conditions NOT met for step 2 modal.",
                    [
                        "team_id" => $team->id,
                        "user_id" => $user->id,
                        "step" => $currentStep,
                        "count" => $studentCount,
                    ]
                );
            }
        } else {
            Log::warning(
                "Student List: No current team found for onboarding check.",
                ["user_id" => $user->id]
            );
        }
    }

    protected function calculateOnboardingState(): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam()->withCount("students")->first(); // Eager load count

        if (!$team) {
            $this->onboardingState = 0;
            return;
        }

        $currentStep = (int) $team->onboarding_step;
        $studentCount = $team->students_count; // Use eager loaded count

        Log::debug("ListStudents Onboarding Check", [
            "team_id" => $team->id,
            "user_id" => $user->id,
            "current_step" => $currentStep,
            "student_count" => $studentCount,
        ]);

        // Only concerned with State 2 for this page
        if (
            $currentStep <= 1 &&
            $studentCount >= self::ONBOARDING_STUDENT_THRESHOLD
        ) {
            $this->onboardingState = 2;
            Log::debug("ListStudents: Setting Onboarding State 2");
        } else {
            $this->onboardingState = 0;
            Log::debug("ListStudents: Setting Onboarding State 0");
        }
    }

    protected function prepareModalData(): void
    {
        // Only calculate URL if the modal might be shown
        if ($this->onboardingState === 2) {
            $team = Auth::user()?->currentTeam()->first();
            $this->activityResourceCreateUrl = $team
                ? ActivityResource::getUrl("create-guide", ["tenant" => $team])
                : "#";
        } else {
            $this->activityResourceCreateUrl = null;
        }
    }

    /**
     * Action to mark the onboarding step as complete (copied from Dashboard)
     */
    public function markOnboardingStepComplete(int $stepJustCompleted): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam()->first();

        if ($team) {
            if ($stepJustCompleted > (int) $team->onboarding_step) {
                Log::info(
                    "Marking onboarding step complete from Student List",
                    [
                        "team_id" => $team->id,
                        "user_id" => $user->id,
                        "step" => $stepJustCompleted,
                        "old_step" => $team->onboarding_step,
                    ]
                );
                $team->update(["onboarding_step" => $stepJustCompleted]);
                // Optionally hide the modal immediately without full page refresh
                $this->showCreateActivityModal = false;
            } else {
                Log::info(
                    "Skipping onboarding step update from Student List (already completed or equal)",
                    [
                        "team_id" => $team->id,
                        "user_id" => $user->id,
                        "step_attempted" => $stepJustCompleted,
                        "current_step" => $team->onboarding_step,
                    ]
                );
                // Hide modal even if skipping update, user interacted
                $this->showCreateActivityModal = false;
            }
        } else {
            Log::warning(
                "Could not mark onboarding step from Student List: No current team found.",
                ["user_id" => $user->id]
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            // Keep the import action if it was here
            // Actions\Action::make('import_students')... (from StudentResource headerActions)
            // Note: You might need to move the import action definition from StudentResource::table
            // into this getHeaderActions method if you want it here alongside Create.
        ];
    }

    // Optional: If you want the modal state to potentially update without a full page reload
    // (e.g., after a table bulk action), you might need listeners.
    // protected function getListeners(): array
    // {
    //     return array_merge(parent::getListeners(), [
    //         'studentsImported' => 'refreshOnboardingState', // Example listener name
    //     ]);
    // }
    //
    // public function refreshOnboardingState(): void
    // {
    //     $this->calculateOnboardingState();
    //     $this->prepareModalData();
    // }
    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            "showCreateActivityModal" => $this->showCreateActivityModal,
            "activityResourceCreateUrl" => $this->activityResourceCreateUrl,
            "studentThreshold" => $this->studentThreshold,
        ]);
    }
}
