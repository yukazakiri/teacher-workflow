<?php

namespace App\Filament\Resources\ClassResource\Pages;

use App\Filament\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

class CreateClass extends CreateRecord
{
    protected static string $resource = ClassResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $team = Filament::getTenant();
        
        // Add team_id and creator_id
        $data['team_id'] = $team->id;
        $data['created_by'] = Auth::id();
        
        // Generate title and description from file metadata will be handled in the model's boot method
        
        return $data;
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        // Create the resource record
        $record = static::getModel()::create($this->mutateFormDataBeforeCreate($data));
        
        // Handle file uploads
        if (!empty($data['files'])) {
            // Check if files is an array or a single path
            if (is_array($data['files'])) {
                foreach ($data['files'] as $file) {
                    // Add the file to the media library
                    $record->addMediaFromDisk($file, 'local')
                        ->toMediaCollection('resources');
                }
            } else {
                // Add the file to the media library
                $record->addMediaFromDisk($data['files'], 'local')
                    ->toMediaCollection('resources');
            }
        }
        
        return $record;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
