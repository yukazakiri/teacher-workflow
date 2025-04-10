@props(['currentTeamId' => null]) {{-- Accept currentTeamId prop --}}

{{-- Direct Message Icon (Home) - Link to Dashboard --}}
<a 
    href="/app" {{-- Assuming /app is your Filament dashboard path --}}
    title="Dashboard / Direct Messages"
    {{-- Conditionally apply active styles if no specific team is selected --}}
    @php
        // $currentTeamId is now available via props
        $isDmActive = $currentTeamId === null;
    @endphp
    class="w-12 h-12 rounded-xl {{ $isDmActive ? 'bg-primary-500 dark:bg-primary-600' : 'bg-gray-500 dark:bg-gray-600 hover:bg-primary-500 dark:hover:bg-primary-600' }} text-white flex items-center justify-center text-2xl font-bold hover:rounded-2xl transition-all duration-200 ease-in-out relative group focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-200 dark:focus:ring-offset-gray-800/50 focus:ring-primary-500"
>
    {{-- Filament User Icon --}}
    @svg('heroicon-o-user-circle', 'w-7 h-7')
    
    {{-- Active Indicator for DM/Home --}}
    @if($isDmActive)
    <span class="absolute -left-2 top-1/2 -translate-y-1/2 h-6 w-1 bg-white dark:bg-white rounded-r-full"></span>
    @endif

    <span class="absolute left-full ml-3 px-2 py-1 bg-gray-900 dark:bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
        Dashboard
    </span>
</a>

<hr class="border-t border-gray-300 dark:border-gray-700 w-8 mx-auto my-2">

{{-- List User's Teams --}}
@auth
    @php
        $user = Auth::user();
        $teams = $user->allTeams();
        $currentTeam = $user->currentTeam;
        // Define colors for UI Avatars
        $avatarBgColor = 'eebebe'; // Hardcoded primary color hex
        $avatarTextColor = 'ffffff'; 
    @endphp

    @if ($teams->count() > 0)
        @foreach ($teams as $team)
            @php
                $isActive = $currentTeam && $currentTeam->id === $team->id;
                // Generate initials for avatar
                $initials = Str::of($team->name)->squish()->explode(' ')->map(fn ($segment) => filled($segment) ? mb_substr($segment, 0, 1) : '' )->join('');
                $avatarName = $initials ?: 'T'; // Fallback if no initials
            @endphp
            <button 
                type="button"
                title="{{ $team->name }}" 
                wire:click.prevent="switchTeam({{ $team->id }})" {{-- Livewire action to switch team --}}
                class="w-12 h-12 rounded-full {{ $isActive ? 'ring-2 ring-white dark:ring-primary-400' : 'bg-gray-300 dark:bg-gray-600' }} text-gray-800 dark:text-gray-200 flex items-center justify-center text-xl font-semibold hover:rounded-2xl hover:ring-2 hover:ring-white dark:hover:ring-primary-400 transition-all duration-200 ease-in-out relative group focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-200 dark:focus:ring-offset-gray-800/50 focus:ring-primary-500"
            >
                {{-- Use UI Avatar with Team Initials --}}
                 <img 
                    class="w-full h-full object-cover rounded-full" 
                    src="https://ui-avatars.com/api/?name={{ urlencode($avatarName) }}&color={{ $avatarTextColor }}&background={{ $avatarBgColor }}&size=48&bold=true" 
                    alt="{{ $team->name }}"
                 >
                
                {{-- Notification Badge (Example - Needs data) --}}
                {{-- <span class="absolute -top-1 -right-1 block h-4 w-4 rounded-full bg-danger-500 dark:bg-danger-600 text-white text-xs flex items-center justify-center ring-2 ring-gray-200 dark:ring-gray-800/50">N</span> --}}
                
                <span class="absolute left-full ml-3 px-2 py-1 bg-gray-900 dark:bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                    {{ $team->name }}
                </span>
                
                 {{-- Active Indicator --}}
                 @if($isActive)
                 <span class="absolute -left-2 top-1/2 -translate-y-1/2 h-6 w-1 bg-white dark:bg-white rounded-r-full"></span>
                 @endif
            </button>
        @endforeach
    @endif
@endauth

{{-- Add Server Button Removed as requested --}} 