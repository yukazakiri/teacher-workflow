<?php

namespace App\Filament\Admin\Resources\ActivityTypeResource\Pages;

use App\Filament\Admin\Resources\ActivityTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActivityTypes extends ListRecords
{
    protected static string $resource = ActivityTypeResource::class;
}
