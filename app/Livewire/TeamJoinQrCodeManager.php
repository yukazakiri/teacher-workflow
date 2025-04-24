<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\QrCodeHelper;
use App\Models\Team;
use App\Models\TeamJoinQrCode;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class TeamJoinQrCodeManager extends Component
{
    public Team $team;
    public string $joinCode = '';
    public bool $showQrCode = false;
    public ?TeamJoinQrCode $activeQrCode = null;
    public int $qrCodeExpiryMinutes = 60;
    public string $qrCodeDescription = '';
    public ?int $qrCodeUseLimit = null;

    public function mount(Team $team): void
    {
        $this->team = $team;
        $this->joinCode = $team->join_code;
        $this->checkActiveQrCode();
    }

    public function regenerateCode(): void
    {
        $this->team->generateJoinCode();
        $this->team->save();
        $this->joinCode = $this->team->join_code; // Update the public property

        Notification::make()
            ->title('Join code regenerated')
            ->success()
            ->send();
    }

    public function toggleShowQrCode(): void
    {
        $this->showQrCode = !$this->showQrCode;
    }

    public function checkActiveQrCode(): void
    {
        $this->activeQrCode = TeamJoinQrCode::where('team_id', $this->team->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function generateQrCode(): void
    {
        if ($this->activeQrCode) {
            $this->activeQrCode->deactivate();
        }

        $this->activeQrCode = TeamJoinQrCode::createForTeam(
            $this->team,
            Auth::user(),
            $this->qrCodeExpiryMinutes,
            $this->qrCodeDescription ?: "Join {$this->team->name}",
            $this->qrCodeUseLimit
        );

        $this->showQrCode = true;

        Notification::make()
            ->title('QR code generated')
            ->success()
            ->send();
    }

    public function extendQrCodeExpiry(int $minutes): void
    {
        if ($this->activeQrCode) {
            $this->activeQrCode->extendExpiry($minutes);
            $this->activeQrCode->refresh();

            Notification::make()
                ->title('QR code expiry extended')
                ->success()
                ->send();
        }
    }

    public function deactivateQrCode(): void
    {
        if ($this->activeQrCode) {
            $this->activeQrCode->deactivate();
            $this->activeQrCode = null;
        }

        Notification::make()
            ->title('QR code deactivated')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.team-join-qr-code-manager');
    }
}
