<?php

namespace App\Filament\Admin\Resources\ActivitySubmissionResource\Pages;

use App\Filament\Admin\Resources\ActivitySubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivitySubmissions extends ListRecords
{
    protected static string $resource = ActivitySubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
