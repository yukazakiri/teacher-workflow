<?php

namespace App\Filament\Pages;

use App\Livewire\ClassResourcesBrowser;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;

class ClassesResources extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.class-resources';

    protected static ?string $navigationGroup = 'Class Resources';

    // protected static ?int $navigationSort = ;

    protected ?string $heading = 'Class Resources Hub';

    protected ?string $subheading = 'Access and manage all your classroom resources, organized by type and category.';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('manage_categories')
                ->label('Manage Categories')
                ->url(route('filament.app.resources.resource-categories.index', ['tenant' => auth()->user()->currentTeam]))
                ->icon('heroicon-o-tag')
                ->color('secondary'),
            \Filament\Actions\Action::make('add_resource')
                ->label('Add New Resource')
                ->url(route('filament.app.resources.class-resources.create', ['tenant' => auth()->user()->currentTeam]))
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

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

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    protected function getViewData(): array
    {
        return [
            'resourceTypes' => [
                [
                    'title' => 'Teacher Materials',
                    'description' => 'Private resources only visible to teachers, including lesson plans, answer keys, and confidential materials.',
                    'icon' => 'heroicon-o-lock-closed',
                    'color' => 'danger',
                    'examples' => 'Lesson Plans, Answer Keys, Exam Solutions, Teacher Guides, Grading Rubrics',
                ],
                [
                    'title' => 'Student Resources',
                    'description' => 'Materials for student learning and reference, accessible to all class members.',
                    'icon' => 'heroicon-o-academic-cap',
                    'color' => 'success',
                    'examples' => 'Syllabi, Handouts, Assignment Instructions, Study Guides, Reading Materials',
                ],
            ]
        ];
    }
}