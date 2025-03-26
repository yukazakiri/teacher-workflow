<?php

namespace App\Filament\Resources\AttendanceQrCodeResource\Pages;

use App\Filament\Resources\AttendanceQrCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceQrCode extends EditRecord
{
    protected static string $resource = AttendanceQrCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
