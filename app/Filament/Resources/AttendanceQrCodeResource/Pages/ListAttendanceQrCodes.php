<?php

namespace App\Filament\Resources\AttendanceQrCodeResource\Pages;

use App\Filament\Resources\AttendanceQrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceQrCodes extends ListRecords
{
    protected static string $resource = AttendanceQrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
