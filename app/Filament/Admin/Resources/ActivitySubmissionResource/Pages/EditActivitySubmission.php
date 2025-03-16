<?php

namespace App\Filament\Admin\Resources\ActivitySubmissionResource\Pages;

use App\Filament\Admin\Resources\ActivitySubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivitySubmission extends EditRecord
{
    protected static string $resource = ActivitySubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
