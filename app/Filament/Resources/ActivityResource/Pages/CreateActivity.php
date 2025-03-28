<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateActivity extends CreateRecord
{
    protected static string $resource = ActivityResource::class;

    // No need to override getFormSchema() - it's inherited from ActivityResource

    // Optional: Mutate data just before creation if needed
    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // Example: Ensure team_id is set if not defaulted in form
    //     $data['team_id'] = $data['team_id'] ?? Auth::user()->currentTeam->id;
    //     return $data;
    // }

    protected function getRedirectUrl(): string
    {
        // Redirect back to index after creation
        return $this->getResource()::getUrl("index");
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Activity created";
    }
}
