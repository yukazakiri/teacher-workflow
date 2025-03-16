<x-filament-panels::page>
    <div class="space-y-8">
        <!-- Current Team Banner -->
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-primary-600 to-primary-400 dark:from-primary-900 dark:to-primary-700">
            <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,#fff,rgba(255,255,255,0.6))]"></div>
            <div class="relative p-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex-shrink-0 bg-white dark:bg-gray-800 rounded-full p-3 shadow-lg">
                            <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-2xl font-bold text-primary-700 dark:text-primary-300">
                                {{ substr($currentTeam->name, 0, 1) }}
                            </div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ $currentTeam->name }}</h1>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm">
                                    <x-heroicon-o-user class="w-3 h-3 mr-1" />
                                    {{ $userRole }}
                                </div>
                                <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm">
                                    <x-heroicon-o-user-group class="w-3 h-3 mr-1" />
                                    {{ $stats['memberCount'] }} {{ Str::plural('member', $stats['memberCount']) }}
                                </div>
                                <div class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm">
                                    <x-heroicon-o-calendar class="w-3 h-3 mr-1" />
                                    Created {{ $stats['createdAt'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($stats['isOwner'])
                        <div class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-amber-500 text-white shadow-sm">
                            <x-heroicon-o-star class="w-4 h-4 mr-1" />
                            Team Owner
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Owned Teams Grid -->
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold tracking-tight">Teams You Own</h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $ownedTeams->count() }} {{ Str::plural('team', $ownedTeams->count()) }}</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($ownedTeams as $team)
                    <div class="group relative bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                        <!-- Color Bar -->
                        @php
                            $colors = [
                                ['from-blue-500 to-indigo-500', 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'],
                                ['from-rose-500 to-pink-500', 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300'],
                                ['from-amber-500 to-yellow-500', 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'],
                                ['from-emerald-500 to-green-500', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'],
                                ['from-violet-500 to-purple-500', 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300'],
                                ['from-cyan-500 to-sky-500', 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900 dark:text-cyan-300']
                            ];
                            $colorIndex = (int)$team->id % count($colors);
                            $gradientClass = $colors[$colorIndex][0];
                            $textBgClass = $colors[$colorIndex][1];
                        @endphp
                        
                        <div class="h-2 w-full bg-gradient-to-r {{ $gradientClass }}"></div>
                        
                        <div class="p-5">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full {{ $textBgClass }} flex items-center justify-center text-lg font-bold">
                                        {{ substr($team->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-lg text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                            {{ $team->name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Created {{ $team->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col items-end gap-2">
                                    @if($currentTeam->id === $team->id)
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300">
                                            <x-heroicon-o-check-circle class="w-3 h-3 mr-1" />
                                            Current
                                        </span>
                                    @endif
                                    
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                                        <x-heroicon-o-star class="w-3 h-3 mr-1" />
                                        Owner
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex flex-wrap gap-2">
                                <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-user-group class="w-3 h-3 mr-1" />
                                    {{ $team->users->count() }} {{ Str::plural('member', $team->users->count()) }}
                                </div>
                                
                                @if($team->pendingInvites > 0)
                                    <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300">
                                        <x-heroicon-o-envelope class="w-3 h-3 mr-1" />
                                        {{ $team->pendingInvites }} {{ Str::plural('invitation', $team->pendingInvites) }}
                                    </div>
                                @endif
                                
                                @if($team->personal_team)
                                    <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                        <x-heroicon-o-home class="w-3 h-3 mr-1" />
                                        Personal
                                    </div>
                                @endif
                            </div>
                            
                            <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                                @if($currentTeam->id !== $team->id)
                                    <form action="{{ route('filament.app.team.switch', $team) }}" method="POST">
                                        @csrf
                                        <x-filament::button type="submit" color="primary" size="sm" class="w-full justify-center">
                                            <x-heroicon-o-arrow-right-circle class="w-4 h-4 mr-1" />
                                            Switch to this team
                                        </x-filament::button>
                                    </form>
                                @else
                                    <div class="flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-check-circle class="w-4 h-4 mr-1 text-success-500" />
                                        You are currently using this team
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Joined Teams Grid -->
        @if($joinedTeams->count() > 0)
        <div>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold tracking-tight">Teams You've Joined</h2>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $joinedTeams->count() }} {{ Str::plural('team', $joinedTeams->count()) }}</span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($joinedTeams as $team)
                    <div class="group relative bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                        <!-- Color Bar -->
                        @php
                            $colors = [
                                ['from-blue-500 to-indigo-500', 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'],
                                ['from-rose-500 to-pink-500', 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300'],
                                ['from-amber-500 to-yellow-500', 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'],
                                ['from-emerald-500 to-green-500', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300'],
                                ['from-violet-500 to-purple-500', 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300'],
                                ['from-cyan-500 to-sky-500', 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900 dark:text-cyan-300']
                            ];
                            $colorIndex = (int)$team->id % count($colors);
                            $gradientClass = $colors[$colorIndex][0];
                            $textBgClass = $colors[$colorIndex][1];
                        @endphp
                        
                        <div class="h-2 w-full bg-gradient-to-r {{ $gradientClass }}"></div>
                        
                        <div class="p-5">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full {{ $textBgClass }} flex items-center justify-center text-lg font-bold">
                                        {{ substr($team->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-lg text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                            {{ $team->name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Owner: {{ $team->owner->name }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col items-end gap-2">
                                    @if($currentTeam->id === $team->id)
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300">
                                            <x-heroicon-o-check-circle class="w-3 h-3 mr-1" />
                                            Current
                                        </span>
                                    @endif
                                    
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                                        <x-heroicon-o-user-plus class="w-3 h-3 mr-1" />
                                        Joined
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex flex-wrap gap-2">
                                <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-user-group class="w-3 h-3 mr-1" />
                                    {{ $team->users->count() }} {{ Str::plural('member', $team->users->count()) }}
                                </div>
                                
                                <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                    <x-heroicon-o-user class="w-3 h-3 mr-1" />
                                    {{ $team->userRole }}
                                </div>
                                
                                <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    <x-heroicon-o-calendar class="w-3 h-3 mr-1" />
                                    Joined {{ $team->joinedAt }}
                                </div>
                            </div>
                            
                            <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                                @if($currentTeam->id !== $team->id)
                                    <form action="{{ route('filament.app.team.switch', $team) }}" method="POST">
                                        @csrf
                                        <x-filament::button type="submit" color="primary" size="sm" class="w-full justify-center">
                                            <x-heroicon-o-arrow-right-circle class="w-4 h-4 mr-1" />
                                            Switch to this team
                                        </x-filament::button>
                                    </form>
                                @else
                                    <div class="flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-check-circle class="w-4 h-4 mr-1 text-success-500" />
                                        You are currently using this team
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
