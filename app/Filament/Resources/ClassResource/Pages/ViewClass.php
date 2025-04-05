<?php

namespace App\Filament\Resources\ClassResource\Pages;

use App\Filament\Resources\ClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
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

    public function getFileDetails(): ?object
    {
        $resource = $this->record;
        $media = $resource->getMedia('resources')->first();

        if (! $media) {
            return null;
        }

        $metadata = $resource->file_metadata; // Access the accessor
        $mimeType = $metadata?->mime_type ?? 'application/octet-stream';
        $fileUrl = $resource->file_url; // Access the accessor

        return (object) [
            'url' => $fileUrl,
            'name' => $media->file_name,
            'size' => $metadata?->size,
            'human_readable_size' => $metadata?->human_readable_size,
            'type' => $mimeType,
            'last_modified' => $metadata?->last_modified?->format('M d, Y h:i A'),
            'isPdf' => str_contains($mimeType, 'pdf'),
            'isImage' => str_contains($mimeType, 'image'),
            'isOfficeDoc' => false, // Office docs not supported anymore
            'hasThumb' => $metadata?->has_thumb ?? false,
            'thumbUrl' => $metadata?->thumb_url,
            'hasPreview' => str_contains($mimeType, 'pdf') || str_contains($mimeType, 'image'),
        ];
    }

    /**
     * Check if the mime type is an Office document
     */
    protected function isOfficeDocument(string $mimeType): bool
    {
        $officeTypes = [
            'msword',
            'wordprocessingml',
            'ms-excel',
            'spreadsheetml',
            'ms-powerpoint',
            'presentationml',
            'opendocument',
        ];

        foreach ($officeTypes as $type) {
            if (str_contains($mimeType, $type)) {
                return true;
            }
        }

        return false;
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

    /**
     * Alias for getFileDetails to maintain compatibility with view.
     */
    public function getMediaDetails(): ?object
    {
        return $this->getFileDetails();
    }
}
