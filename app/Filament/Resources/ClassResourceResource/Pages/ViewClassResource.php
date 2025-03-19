<?php

namespace App\Filament\Resources\ClassResourceResource\Pages;

use App\Filament\Resources\ClassResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClassResource extends ViewRecord
{
    protected static string $resource = ClassResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
} 