<?php

namespace App\Filament\Resources\ClassResourceResource\Pages;

use App\Filament\Resources\ClassResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class ListClassResources extends ListRecords
{
    protected static string $resource = ClassResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
} 