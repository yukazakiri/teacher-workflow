<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Team;
use App\Models\TeamJoinQrCode;
use App\Models\Student;
use App\Helpers\StudentHelper;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Jetstream\Jetstream;
use Livewire\Component;
use App\Models\User;

class TeamJoinInstant extends Component
{
    public ?string $code = null;
    public ?TeamJoinQrCode $qrCode = null;
    public ?Team $team = null;
    public bool $isLoading = true;
    public bool $isValid = false;
    public bool $isExpired = false;
    public bool $isUsed = false;
    public bool $isAlreadyMember = false;
    public bool $joinSuccess = false;
    public string $errorMessage = '';

    public function mount(string $code): void
    {
        $this->code = $code;
        $this->validateQrCode();
    }

    public function validateQrCode(): void
    {
        $this->isLoading = true;
        $this->isValid = false;
        $this->isExpired = false;
        $this->isUsed = false;
        $this->isAlreadyMember = false;
        $this->joinSuccess = false;
        $this->errorMessage = '';

        try {
            // Find the QR code
            $this->qrCode = TeamJoinQrCode::where('code', $this->code)
                ->where('is_active', true)
                ->first();

            if (!$this->qrCode) {
                $this->errorMessage = 'The invitation link is invalid or has been disabled.';
                $this->isValid = false;
                return;
            }

            $this->team = $this->qrCode->team;

            // Check if expired
            if ($this->qrCode->isExpired()) {
                $this->errorMessage = 'This invitation has expired.';
                $this->isExpired = true;
                $this->isValid = false;
                return;
            }

            // Check if the use limit is reached
            if ($this->qrCode->use_limit !== null && $this->qrCode->use_count >= $this->qrCode->use_limit) {
                $this->errorMessage = 'This invitation has reached its usage limit.';
                $this->isUsed = true;
                $this->isValid = false;
                return;
            }

            // Check if user is already a member of the team
            $user = Auth::user();
            if ($user->belongsToTeam($this->team)) {
                $this->errorMessage = 'You are already a member of this team.';
                $this->isAlreadyMember = true;
                $this->isValid = false;
                return;
            }

            // QR code is valid
            $this->isValid = true;

        } catch (\Exception $e) {
            Log::error('Error validating QR code: ' . $e->getMessage());
            $this->errorMessage = 'An error occurred. Please try again later.';
            $this->isValid = false;
        } finally {
            $this->isLoading = false;
        }
    }

    public function joinTeam(): void
    {
        try {
            // Validate QR code again before processing
            $this->validateQrCode();
            
            if (!$this->isValid) {
                return;
            }

            $user = Auth::user();
            
            // Add user to the team
            $user->teams()->attach($this->team, ['role' => 'member']);

            // Create a student record
            StudentHelper::createStudentRecord($user, $this->team);

            // Record usage of the QR code
            $this->qrCode->recordUsage();
            
            // If this was the last use, deactivate the QR code
            if ($this->qrCode->use_limit !== null && $this->qrCode->use_count >= $this->qrCode->use_limit) {
                $this->qrCode->deactivate();
            }

            // Set current team for the user
            $user->switchTeam($this->team);
            
            $this->joinSuccess = true;

            Notification::make()
                ->title('You have successfully joined the team!')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error joining team: ' . $e->getMessage());
            $this->errorMessage = 'An error occurred while joining the team. Please try again later.';
            
            Notification::make()
                ->title('Failed to join team')
                ->body($this->errorMessage)
                ->danger()
                ->send();
        }
    }

    /**
     * Create a student record for the user in the team
     */
    private function createStudentRecord(User $user, Team $team): void
    {
        StudentHelper::createStudentRecord($user, $team);
    }

    public function render(): View
    {
        return view('livewire.team-join-instant');
    }
} 