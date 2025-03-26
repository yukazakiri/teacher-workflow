<?php

namespace App\Filament\Resources\ResourceCategoryResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ResourceCategoryResource;

class ListResourceCategories extends ListRecords
{
    protected static string $resource = ResourceCategoryResource::class;

    public function mount(): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        
        if (!$team || !$team->userIsOwner($user)) {
            Notification::make()
                ->title('Access Denied')
                ->body('Only team owners can access the exam management.')
                ->danger()
                ->send();
                
            redirect()->route('filament.app.pages.dashboard', ['tenant' => $team->id ]);
            
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