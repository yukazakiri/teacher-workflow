<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditActivity extends EditRecord
{
    protected static string $resource = ActivityResource::class;

    // No need to override getFormSchema() - it's inherited from ActivityResource

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            // Add other header actions like View, Duplicate maybe?
            // Actions\Action::make('duplicate') // Example
            //     ->label('Duplicate')
            //     ->icon('heroicon-o-document-duplicate')
            //     ->action('callDuplicateAction'), // Define callDuplicateAction method if needed here
        ];
    }

    // Optional: Mutate data just before saving if needed
    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //    // Example: Update an 'updated_by' field
    //    // $data['updated_by'] = Auth::id();
    //    return $data;
    // }

    protected function getRedirectUrl(): string
    {
        // Redirect back to index after saving
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Activity updated';
    }
}
