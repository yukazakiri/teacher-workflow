<?php

namespace App\Filament\Resources\ClassResourceResource\Pages;

use App\Filament\Resources\ClassResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassResource extends EditRecord
{
    protected static string $resource = ClassResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 