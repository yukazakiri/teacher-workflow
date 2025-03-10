<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Laravel\Jetstream\TeamInvitation;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class PendingInvitationsTableWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 3;
    
    public function table(Table $table): Table
    {
        $currentTeam = Auth::user()->currentTeam;
        
        return $table
            ->heading('Pending Invitations')
            ->description('Manage your team invitations')
            ->query(
                TeamInvitation::where('team_id', $currentTeam->id)
            )
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('role')
                    ->label('Role')
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'editor' => 'warning',
                        default => 'gray',
                    }),
                
                TextColumn::make('created_at')
                    ->label('Invited')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn () => $currentTeam->user_id === Auth::id())
                    ->action(function (TeamInvitation $record) {
                        $record->delete();
                        
                        Notification::make()
                            ->title('Invitation Cancelled')
                            ->body('The invitation has been cancelled successfully.')
                            ->success()
                            ->send();
                    }),
                
                Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('gray')
                    ->visible(fn () => $currentTeam->user_id === Auth::id())
                    ->action(function (TeamInvitation $record) {
                        $record->sendInvitationNotification();
                        
                        Notification::make()
                            ->title('Invitation Resent')
                            ->body('The invitation has been resent successfully.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('cancel_selected')
                    ->label('Cancel Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn () => $currentTeam->user_id === Auth::id())
                    ->action(function (Collection $records) {
                        $records->each->delete();
                        
                        Notification::make()
                            ->title('Invitations Cancelled')
                            ->body('The selected invitations have been cancelled.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No pending invitations')
            ->emptyStateDescription('There are no pending invitations for this team.')
            ->emptyStateIcon('heroicon-o-envelope')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
