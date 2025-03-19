<?php

namespace App\Filament\Pages;

use App\Livewire\ClassResourcesBrowser;
use Filament\Pages\Page;

class ClassesResources extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.class-resources';
    
    protected static ?string $navigationGroup = 'Class Resources';
    
    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return 'Classes Resources';
    }

    public function getTitle(): string
    {
        return 'Classes Resources';
    }
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
} 