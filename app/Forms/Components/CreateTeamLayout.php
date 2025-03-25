<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Component;

class CreateTeamLayout extends Component
{
    protected string $view = 'forms.components.create-team-layout';

    public static function make(): static
    {
        return app(static::class);
    }
}
