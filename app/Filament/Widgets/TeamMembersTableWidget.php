<?php

namespace App\Filament\Widgets;

use App\Models\Team;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;

class TeamMembersTableWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2;
    
    public function table(Table $table): Table
    {
        $currentTeam = Auth::user()->currentTeam;
        
        return $table
            ->heading('Team Members')
            ->description('Members of your current team')
            ->query(
                User::whereHas('teams', function (Builder $query) use ($currentTeam) {
                    $query->where('teams.id', $currentTeam->id);
                })
            )
            ->columns([
                ImageColumn::make('profile_photo_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn (User $user) => 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&color=7F9CF5&background=EBF4FF')
                    ->size(40),
                
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('role')
                    ->label('Role')
                    ->formatStateUsing(function (User $user) use ($currentTeam) {
                        // Check if user is the team owner
                        if ($currentTeam->user_id === $user->id) {
                            return 'Owner';
                        }
                        
                        // Get role from pivot table
                        $pivotRole = DB::table('team_user')
                            ->where('team_id', $currentTeam->id)
                            ->where('user_id', $user->id)
                            ->value('role');
                        
                        if ($pivotRole === 'admin') {
                            return 'Admin';
                        } elseif ($pivotRole === 'editor') {
                            return 'Editor';
                        }
                        
                        return 'Member';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Owner' => 'success',
                        'Admin' => 'danger',
                        'Editor' => 'warning',
                        default => 'gray',
                    }),
                
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Action::make('manage')
                    ->label('Manage')
                    ->icon('heroicon-o-cog')
                    ->visible(fn (User $user) => $currentTeam->user_id === Auth::id() && $user->id !== Auth::id())
                    ->url(fn (User $user) => route('teams.show', ['team' => $currentTeam->id]) . '?tab=members'),
            ])
            ->paginated([5, 10, 25, 50])
            ->defaultPaginationPageOption(5);
    }
}
