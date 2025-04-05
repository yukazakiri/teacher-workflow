<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\StudentResource;
use App\Models\Team;
use Filament\Resources\Pages\CreateRecord; // Import Dashboard
use Illuminate\Support\Facades\Auth; // Import Team

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    // Property to track if redirection is needed
    // protected bool $shouldRedirectToDashboard = false; // Alternative approach

    protected function afterCreate(): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        if ($team && $team->onboarding_step === 0) {
            $currentStudentCount = $team->students()->count();
            if ($currentStudentCount > 0) {
                // Or >= threshold if step 1 had a threshold
                $team->update(['onboarding_step' => 1]); // Optionally mark step 1 done here
            }
        }
    }

    /**
     * Hook before saving the form data.
     * Ensures team_id is set correctly if not already present.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['team_id']) && Auth::user()?->currentTeam) {
            $data['team_id'] = Auth::user()->currentTeam->id;
        }

        return $data;
    }
}
