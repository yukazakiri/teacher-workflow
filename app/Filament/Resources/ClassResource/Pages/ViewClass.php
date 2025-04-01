<?php

namespace App\Filament\Resources\ClassResource\Pages;

use App\Filament\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ViewClass extends ViewRecord
{
    protected static string $resource = ClassResource::class;
    
    protected static string $view = 'filament.resources.class-resource.pages.view-class-resource';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getMediaDetails(): ?object
    {
        $media = $this->record->getFirstMedia('resources');
        if (!$media) {
            return null;
        }

        return (object) [
            'url' => $media->getFullUrl(),
            'name' => $media->file_name,
            'size' => $media->human_readable_size,
            'type' => $media->mime_type,
            'isPdf' => str_contains($media->mime_type, 'pdf'),
            'isImage' => str_contains($media->mime_type, 'image'),
            'isOfficeDoc' => str_contains($media->mime_type, 'msword') ||
                             str_contains($media->mime_type, 'wordprocessingml') ||
                             str_contains($media->mime_type, 'ms-excel') ||
                             str_contains($media->mime_type, 'spreadsheetml') ||
                             str_contains($media->mime_type, 'ms-powerpoint') ||
                             str_contains($media->mime_type, 'presentationml'),
        ];
    }

    public function getAccessLevelBadge(): array
    {
        $state = $this->record->access_level;
        $label = match ($state) {
            'all' => 'All Members',
            'teacher' => 'Teachers Only',
            'owner' => 'Owner Only',
            default => $state,
        };
        $color = match ($state) {
            'all' => 'success',
            'teacher' => 'warning',
            'owner' => 'danger',
            default => 'gray',
        };
        return ['label' => $label, 'color' => $color];
    }

    public function getFormattedDescription(): HtmlString
    {
        return new HtmlString(Str::markdown($this->record->description ?? ''));
    }
}
