<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login;

class LoginPage extends Login
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.login-page';
}
