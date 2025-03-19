<?php

namespace App\Filament\Resources\ClassResourceResource\Pages;

use App\Filament\Resources\ClassResourceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class CreateClassResource extends CreateRecord
{
    protected static string $resource = ClassResourceResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = Filament::getTenant()->id;
        $data['created_by'] = Auth::id();
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 