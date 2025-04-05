<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use Filament\Resources\Pages\Page;

class CreateActivityCustom extends Page
{
    // This makes our page work with Filament resources
    protected static string $resource = ActivityResource::class;

    // Use our custom Blade view that embeds the Livewire component
    protected static string $view = 'filament.resources.activity-resource.pages.create-activity-custom';

    // Define the route for this custom page
    public static function getRoute(): string
    {
        return 'create';
    }

    // Set the page title
    public function getTitle(): string
    {
        return __('Create Activity');
    }
}
