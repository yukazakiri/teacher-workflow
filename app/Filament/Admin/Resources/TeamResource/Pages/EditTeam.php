<?php

namespace App\Filament\Admin\Resources\TeamResource\Pages;

use App\Filament\Admin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('manageMembers')
                ->label('Manage Members')
                ->icon('heroicon-o-users')
                ->color('success')
                ->url(fn () => TeamResource::getUrl('members', ['record' => $this->record])),
        ];
    }
}
