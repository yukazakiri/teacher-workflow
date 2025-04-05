<?php

namespace App\Filament\Resources\ClassResource\Pages;

use App\Filament\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditClass extends EditRecord
{
    protected static string $resource = ClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Update the record with the form data
        $record->update($data);

        // Handle file uploads
        if (! empty($data['files'])) {
            // Check if files is an array or a single path
            if (is_array($data['files'])) {
                // Clear existing media collection before adding new files
                $record->clearMediaCollection('resources');

                foreach ($data['files'] as $file) {
                    // Add the file to the media library
                    $record->addMediaFromDisk($file, 'local')
                        ->toMediaCollection('resources');
                }
            } else {
                // Clear existing media collection before adding new files
                $record->clearMediaCollection('resources');

                // Add the file to the media library
                $record->addMediaFromDisk($data['files'], 'local')
                    ->toMediaCollection('resources');
            }
        }

        return $record;
    }
}
