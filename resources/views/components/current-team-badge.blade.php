@php
    use Illuminate\Support\Facades\Auth;
    
    $user = Auth::user();
    $currentTeam = $user->currentTeam;
    
    // Get all teams the user belongs to
    $allTeams = $user->allTeams();
    
    // Generate a consistent color based on team ID
    $colors = [
        'primary', 'success', 'warning', 'danger', 'info'
    ];
    $colorIndex = (int)$currentTeam->id % count($colors);
    $color = $colors[$colorIndex];
@endphp

<div 
    x-data="{ open: false }" 
    @click.away="open = false" 
    @keydown.escape.window="open = false"
    class="relative"
>
    <button 
        @click="open = !open" 
        class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
    >
        <x-filament::badge :color="$color" class="font-medium">
            <div class="flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-current opacity-50"></span>
                <span>{{ $currentTeam->name }}</span>
            </div>
        </x-filament::badge>
        
        <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-500 dark:text-gray-400" />
    </button>
    
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-100" 
        x-transition:enter-start="transform opacity-0 scale-95" 
        x-transition:enter-end="transform opacity-100 scale-100" 
        x-transition:leave="transition ease-in duration-75" 
        x-transition:leave-start="transform opacity-100 scale-100" 
        x-transition:leave-end="transform opacity-0 scale-95" 
        class="absolute right-0 z-50 mt-2 w-64 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        style="display: none;"
    >
        <div class="p-2 space-y-1">
            <div class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Your classes
            </div>
            
            @foreach($allTeams as $team)
                @php
                    $isCurrentTeam = $currentTeam->id === $team->id;
                    $teamColorIndex = (int)$team->id % count($colors);
                    $teamColor = $colors[$teamColorIndex];
                @endphp
                
                <div class="relative">
                    @if($isCurrentTeam)
                        <div class="absolute inset-y-0 left-0 w-1 bg-{{ $teamColor }}-500 rounded-l-md"></div>
                    @endif
                    
                    <div class="flex items-center justify-between px-3 py-2 rounded-md {{ $isCurrentTeam ? 'bg-gray-100 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-{{ $teamColor }}-100 dark:bg-{{ $teamColor }}-900 flex items-center justify-center text-sm font-bold text-{{ $teamColor }}-700 dark:text-{{ $teamColor }}-300">
                                {{ substr($team->name, 0, 1) }}
                            </div>
                            
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $team->name }}
                                </div>
                                
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    @if($team->user_id === $user->id)
                                        Owner
                                    @else
                                        Member
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        @if(!$isCurrentTeam)
                            <form action="{{ route('filament.app.team.switch', $team) }}" method="POST">
                                @csrf
                                <x-filament::button type="submit" size="xs" spa="true" color="primary">
                                    Switch
                                </x-filament::button>
                            </form>
                        @else
                            <span class="text-xs text-success-600 dark:text-success-400">
                                Current
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
            
            <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
            
            <a href="{{ route('filament.app.tenant.profile', ['tenant' => $currentTeam->id]) }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">
                <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                <span>Class Settings</span>
            </a>
            
            <a href="{{ route('filament.app.tenant.registration') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-md">
                <x-heroicon-o-plus-circle class="w-4 h-4" />
                <span>Create New Class</span>
            </a>
        </div>
    </div>
</div> 