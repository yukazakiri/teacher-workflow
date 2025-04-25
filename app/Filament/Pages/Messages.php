<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Messages extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.messages';

    public ?string $channelId = null;

    public function mount()
    {
        $this->channelId = request()->query('channel');
    }
}
