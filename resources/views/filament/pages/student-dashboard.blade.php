<x-filament-panels::page>
    @if (!isset($hasStudentProfile) || !$hasStudentProfile)
        <div class="flex items-center justify-center p-8 bg-white dark:bg-gray-800 shadow rounded-xl">
            <div class="text-center max-w-md">
                <div class="flex justify-center mb-4">
                    <x-heroicon-o-exclamation-circle class="h-12 w-12 text-warning-500" />
                </div>
                <h2 class="text-2xl font-bold mb-3">{{ __('Student Profile Not Found') }}</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ __('Please contact your teacher to set up your student profile.') }}</p>
                <a href="#" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <x-heroicon-o-chat-bubble-left-right class="mr-2 h-4 w-4" />
                    {{ __('Contact Teacher') }}
                </a>
            </div>
        </div>
    @else
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-primary-500/10 to-info-500/10 dark:from-primary-800/20 dark:to-info-800/20 rounded-xl p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ __('Welcome back, ') . explode(' ', Auth::user()->name)[0] }}!
                    </h2>
                    <p class="text-gray-600 dark:text-gray-300">{{ __('Here\'s an overview of your academic progress') }}</p>
                </div>
                
                <div class="flex items-center bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm">
                    <div class="flex items-center justify-center bg-primary-100 dark:bg-primary-900/50 h-14 w-14 rounded-full mr-4">
                        <span class="text-2xl font-bold text-primary-700 dark:text-primary-400">{{ $academics['letter_grade'] }}</span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Overall Grade') }}</div>
                        <div class="text-lg font-bold {{ $academics['average_grade'] >= 70 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                            {{ $academics['average_grade'] }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Academic Stats Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="bg-primary-500/10 dark:bg-primary-500/20 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-medium flex items-center">
                        <x-heroicon-o-academic-cap class="h-5 w-5 mr-2 text-primary-500" />
                        {{ __('Academic Progress') }}
                    </h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $academics['total_activities'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Total Activities') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $academics['completed_activities'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Completed') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $academics['graded_submissions'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Graded') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $academics['completion_rate'] }}%</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Completion') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Stats Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="bg-info-500/10 dark:bg-info-500/20 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-medium flex items-center">
                        <x-heroicon-o-clock class="h-5 w-5 mr-2 text-info-500" />
                        {{ __('Attendance') }}
                    </h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-info-600 dark:text-info-400">{{ $attendance['percentage'] }}%</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Attendance Rate') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-info-600 dark:text-info-400">{{ $attendance['days_present'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Days Present') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-info-600 dark:text-info-400">{{ $attendance['late_days'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Late Days') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-info-600 dark:text-info-400">{{ $attendance['absent_days'] }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Absent Days') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Due Soon Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden col-span-1 md:col-span-2 lg:col-span-2">
                <div class="bg-warning-500/10 dark:bg-warning-500/20 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-medium flex items-center">
                        <x-heroicon-o-bell-alert class="h-5 w-5 mr-2 text-warning-500" />
                        {{ __('Due Soon') }}
                    </h3>
                </div>
                <div class="p-4">
                    @if(count($upcomingAssignments) > 0)
                        <div class="flex flex-col space-y-3">
                            @foreach(array_slice($upcomingAssignments, 0, 2) as $assignment)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center">
                                        @php
                                            $formatIcon = match($assignment['format'] ?? 'assignment') {
                                                'quiz' => 'heroicon-o-clipboard-document-check',
                                                'presentation' => 'heroicon-o-presentation-chart-line',
                                                'project' => 'heroicon-o-document-chart-bar',
                                                'discussion' => 'heroicon-o-chat-bubble-left-right',
                                                default => 'heroicon-o-document-text'
                                            };
                                        @endphp
                                        <x-dynamic-component :component="$formatIcon" class="h-8 w-8 mr-3 text-{{ $assignment['color_class'] }}-500" />
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ $assignment['title'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $assignment['due_string'] }} • {{ $assignment['total_points'] }} points</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium 
                                        @if($assignment['status'] === 'completed')
                                            bg-success-100 text-success-800 dark:bg-success-800/30 dark:text-success-300
                                        @elseif($assignment['status'] === 'submitted')
                                            bg-primary-100 text-primary-800 dark:bg-primary-800/30 dark:text-primary-300
                                        @elseif($assignment['status'] === 'in_progress')
                                            bg-info-100 text-info-800 dark:bg-info-800/30 dark:text-info-300
                                        @else
                                            bg-warning-100 text-warning-800 dark:bg-warning-800/30 dark:text-warning-300
                                        @endif
                                    ">
                                        {{ ucfirst($assignment['status']) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <x-heroicon-o-check-circle class="h-12 w-12 mx-auto text-success-500 mb-3" />
                            <p class="text-gray-500 dark:text-gray-400">{{ __('No upcoming assignments due') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Upcoming Assignments Column -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-medium flex items-center">
                            <x-heroicon-o-calendar class="h-5 w-5 mr-2 text-primary-500" />
                            {{ __('Upcoming Assignments') }}
                        </h3>
                        <a href="#" class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center">
                            {{ __('View all') }}
                            <x-heroicon-o-chevron-right class="h-4 w-4 ml-1" />
                        </a>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($upcomingAssignments as $assignment)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        @php
                                            $statusColor = match($assignment['status']) {
                                                'completed' => 'text-success-500',
                                                'submitted' => 'text-primary-500',
                                                'in_progress' => 'text-info-500',
                                                default => 'text-warning-500'
                                            };
                                            
                                            $statusIcon = match($assignment['status']) {
                                                'completed' => 'heroicon-o-check-circle',
                                                'submitted' => 'heroicon-o-paper-airplane',
                                                'in_progress' => 'heroicon-o-pencil-square',
                                                default => 'heroicon-o-clock'
                                            };
                                        @endphp
                                        <x-dynamic-component :component="$statusIcon" class="h-6 w-6 {{ $statusColor }}" />
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex justify-between">
                                            <h4 class="text-base font-medium text-gray-900 dark:text-white">{{ $assignment['title'] }}</h4>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($assignment['status'] === 'completed')
                                                    bg-success-100 text-success-800 dark:bg-success-800/30 dark:text-success-300
                                                @elseif($assignment['status'] === 'submitted')
                                                    bg-primary-100 text-primary-800 dark:bg-primary-800/30 dark:text-primary-300
                                                @elseif($assignment['status'] === 'in_progress')
                                                    bg-info-100 text-info-800 dark:bg-info-800/30 dark:text-info-300
                                                @else
                                                    bg-warning-100 text-warning-800 dark:bg-warning-800/30 dark:text-warning-300
                                                @endif
                                            ">
                                                {{ ucfirst($assignment['status']) }}
                                            </span>
                                        </div>
                                        <div class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                            <x-heroicon-o-calendar class="mr-1.5 h-4 w-4 flex-shrink-0" />
                                            <span>{{ $assignment['due_string'] }}</span>
                                            <span class="mx-2">•</span>
                                            <x-heroicon-o-star class="mr-1.5 h-4 w-4 flex-shrink-0" />
                                            <span>{{ $assignment['total_points'] }} {{ __('points') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-center">
                                <x-heroicon-o-document-check class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ __('No Upcoming Assignments') }}</h3>
                                <p class="text-gray-500 dark:text-gray-400">{{ __('Enjoy your free time!') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                
                <!-- Attendance Overview -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-medium flex items-center">
                            <x-heroicon-o-calendar-days class="h-5 w-5 mr-2 text-primary-500" />
                            {{ __('Attendance Overview') }}
                        </h3>
                        <div class="flex items-center space-x-1 text-xs">
                            <span class="inline-block w-3 h-3 rounded-full bg-success-500"></span>
                            <span class="text-gray-500 dark:text-gray-400 mr-2">{{ __('Present') }}</span>
                            <span class="inline-block w-3 h-3 rounded-full bg-warning-500"></span>
                            <span class="text-gray-500 dark:text-gray-400 mr-2">{{ __('Late') }}</span>
                            <span class="inline-block w-3 h-3 rounded-full bg-danger-500"></span>
                            <span class="text-gray-500 dark:text-gray-400 mr-2">{{ __('Absent') }}</span>
                            <span class="inline-block w-3 h-3 rounded-full bg-info-500"></span>
                            <span class="text-gray-500 dark:text-gray-400">{{ __('Excused') }}</span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <!-- Attendance Progress Bar -->
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Attendance Rate') }}</span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $attendance['percentage'] }}%</span>
                            </div>
                            <div class="h-2.5 w-full bg-gray-200 rounded-full dark:bg-gray-700 overflow-hidden">
                                <div class="h-2.5 rounded-full {{ $attendance['percentage'] >= 90 ? 'bg-success-500' : ($attendance['percentage'] >= 75 ? 'bg-warning-500' : 'bg-danger-500') }}" 
                                     style="width: {{ $attendance['percentage'] }}%"></div>
                            </div>
                        </div>
                        
                        <!-- Attendance Calendar -->
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <div class="grid grid-cols-7 gap-1 sm:gap-2">
                                <!-- Calendar header -->
                                @foreach(['M', 'T', 'W', 'T', 'F', 'S', 'S'] as $day)
                                    <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">{{ $day }}</div>
                                @endforeach
                                
                                <!-- Attendance indicators -->
                                @foreach($attendance['calendar'] as $day)
                                    @php
                                        $colorClass = match($day['status']) {
                                            'present' => 'bg-success-500',
                                            'late' => 'bg-warning-500',
                                            'absent' => 'bg-danger-500',
                                            'excused' => 'bg-info-500',
                                            default => 'bg-gray-300 dark:bg-gray-600'
                                        };
                                    @endphp
                                    <div class="aspect-square rounded-full {{ $colorClass }} hover:ring-2 hover:ring-offset-2 hover:ring-gray-300 dark:hover:ring-gray-600 transition-all cursor-help" 
                                         title="{{ ucfirst($day['status']) }} - {{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Grades Column -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-lg font-medium flex items-center">
                            <x-heroicon-o-chart-bar class="h-5 w-5 mr-2 text-primary-500" />
                            {{ __('Recent Grades') }}
                        </h3>
                        <a href="#" class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center">
                            {{ __('View all') }}
                            <x-heroicon-o-chevron-right class="h-4 w-4 ml-1" />
                        </a>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($recentGrades as $grade)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $grade['activity_title'] }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('Graded ') }} {{ $grade['graded_at']->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="flex items-center space-x-2">
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $grade['score'] }} / {{ $grade['total_points'] }}
                                            </div>
                                            <div class="text-lg font-bold w-8 h-8 flex items-center justify-center rounded-full
                                                @if($grade['percentage'] >= 90)
                                                    bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400
                                                @elseif($grade['percentage'] >= 80)
                                                    bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400
                                                @elseif($grade['percentage'] >= 70)
                                                    bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400
                                                @elseif($grade['percentage'] >= 60)
                                                    bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400
                                                @else
                                                    bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400
                                                @endif
                                            ">
                                                {{ $grade['letter_grade'] }}
                                            </div>
                                        </div>
                                        <div class="mt-1 h-1.5 w-full bg-gray-200 rounded-full dark:bg-gray-700">
                                            <div class="h-1.5 rounded-full 
                                                @if($grade['percentage'] >= 90)
                                                    bg-success-500
                                                @elseif($grade['percentage'] >= 80)
                                                    bg-success-500
                                                @elseif($grade['percentage'] >= 70)
                                                    bg-info-500
                                                @elseif($grade['percentage'] >= 60)
                                                    bg-warning-500
                                                @else
                                                    bg-danger-500
                                                @endif
                                            " style="width: {{ $grade['percentage'] }}%"></div>
                                        </div>
                                    </div>
                                </div>
                                @if($grade['feedback'])
                                    <div class="mt-2 text-sm italic text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 p-2 rounded">
                                        "{{ Str::limit($grade['feedback'], 100) }}"
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="p-6 text-center">
                                <x-heroicon-o-clipboard-document-check class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ __('No Grades Yet') }}</h3>
                                <p class="text-gray-500 dark:text-gray-400">{{ __('Complete assignments to see your grades here') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page> 