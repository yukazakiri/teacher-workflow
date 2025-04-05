<?php

namespace App\Filament\Resources\ExamResource\Pages;

use App\Filament\Resources\ExamResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListExams extends ListRecords
{
    protected static string $resource = ExamResource::class;

    public function mount(): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam;

        if (! $team || ! $team->userIsOwner($user)) {
            Notification::make()
                ->title('Access Denied')
                ->body('Only team owners can access the exam management.')
                ->danger()
                ->send();

            redirect()->route('filament.app.pages.dashboard', ['tenant' => $team->id]);

        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
