<?php

namespace App\Filament\Admin\Resources\ExamSubmissionResource\Pages;

use App\Filament\Admin\Resources\ExamSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamSubmissions extends ListRecords
{
    protected static string $resource = ExamSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
