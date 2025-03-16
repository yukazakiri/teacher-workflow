<?php

namespace App\Filament\Admin\Resources\ExamSubmissionResource\Pages;

use App\Filament\Admin\Resources\ExamSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExamSubmission extends EditRecord
{
    protected static string $resource = ExamSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
