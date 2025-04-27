<x-filament-panels::page>
    <!-- Include the LinkStudentForm component -->
    @livewire('link-student-form')

    <div class="flex flex-col gap-y-8">
        <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
            <div class="flex flex-col gap-y-4">
                <div class="flex items-center gap-4">
                    <div class="bg-primary-100 dark:bg-primary-500/20 p-3 rounded-full">
                        <x-heroicon-o-users class="h-8 w-8 text-primary-600 dark:text-primary-400"/>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold tracking-tight">{{ __('Parent Dashboard') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Monitor your child\'s academic performance and activities') }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if(!$hasLinkedStudents)
            <!-- No linked students message -->
            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center">
                <div class="flex flex-col items-center justify-center gap-4">
                    <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-full">
                        <x-heroicon-o-user-plus class="h-8 w-8 text-gray-500 dark:text-gray-400"/>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('No Students Linked') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Please link a student account to view their progress.') }}</p>
                    </div>
                    <button
                        type="button"
                        onclick="Livewire.dispatch('showLinkStudentForm')"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                    >
                        <x-heroicon-m-link class="-ml-1 mr-2 h-4 w-4" />
                        {{ __('Link Student') }}
                    </button>
                </div>
            </div>
        @else
            <!-- Student Selection Tabs (if multiple students) -->
            @if(count($studentDetails) > 1)
                <div x-data="{ 
                    activeTab: 0,
                    init() {
                        this.$nextTick(() => {
                            this.updateTabIndicator();
                        });
                    },
                    updateTabIndicator() {
                        const activeTabEl = this.$refs.tabContainer.children[this.activeTab];
                        const indicator = this.$refs.indicator;
                        indicator.style.width = `${activeTabEl.offsetWidth}px`;
                        indicator.style.left = `${activeTabEl.offsetLeft}px`;
                    },
                    changeTab(index) {
                        this.activeTab = index;
                        this.updateTabIndicator();
                    }
                }" class="bg-white dark:bg-gray-800 shadow rounded-xl p-4">
                    <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-4 px-2">{{ __('Your Children') }}</h3>
                    <div class="relative">
                        <div x-ref="tabContainer" class="flex border-b border-gray-200 dark:border-gray-700">
                            @foreach($studentDetails as $index => $details)
                                <button 
                                    @click="changeTab({{ $index }})" 
                                    :class="{'text-primary-600 dark:text-primary-400 font-medium': activeTab === {{ $index }}, 'text-gray-500 dark:text-gray-400': activeTab !== {{ $index }}}"
                                    class="px-4 py-2 text-sm hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none transition"
                                >
                                    {{ $details['student']->name }}
                                </button>
                            @endforeach
                        </div>
                        <div 
                            x-ref="indicator" 
                            class="absolute bottom-0 h-0.5 bg-primary-600 dark:bg-primary-400 transition-all duration-300"
                        ></div>
                    </div>
                    
                    <!-- Tab Content -->
                    @foreach($studentDetails as $index => $details)
                        <div x-show="activeTab === {{ $index }}" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="mt-4">
                            <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg mb-4">
                                <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center text-primary-600 dark:text-primary-400 text-xl font-bold">
                                    {{ substr($details['student']->name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-lg font-medium">{{ $details['student']->name }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $details['student']->student_id }}</p>
                                    <div class="mt-1 flex gap-2">
                                        <span class="inline-flex items-center text-xs bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-300 px-2 py-0.5 rounded-full">
                                            {{ $details['student']->status ?? 'Active' }}
                                        </span>
                                        @if($details['student']->gender)
                                            <span class="inline-flex items-center text-xs bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-2 py-0.5 rounded-full">
                                                {{ $details['student']->gender }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Active Student Details -->
            <div x-data="{ activeTab: 0 }">
                @foreach($studentDetails as $index => $details)
                    <div x-show="activeTab === {{ $index }} || {{ count($studentDetails) === 1 ? 'true' : 'false' }}">
                        <!-- Student Performance Overview -->
                        <div class="grid gap-6 md:grid-cols-3">
                            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                                <div class="flex flex-col items-center">
                                    <div class="inline-flex p-3 rounded-full bg-success-100 text-success-600 dark:bg-success-700/20 dark:text-success-400 mb-4">
                                        <x-heroicon-o-academic-cap class="h-6 w-6"/>
                                    </div>
                                    <h3 class="text-lg font-medium text-center">{{ __('Overall Grade') }}</h3>
                                    @if(isset($details['academics']['average_grade']) && $details['academics']['average_grade'])
                                        <span class="text-3xl font-bold mt-2">{{ $details['academics']['letter_grade'] ?? 'N/A' }} ({{ $details['academics']['average_grade'] }}%)</span>
                                    @else
                                        <span class="text-3xl font-bold mt-2">{{ $details['academics']['letter_grade'] ?? 'N/A' }}</span>
                                    @endif
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Based on graded submissions') }}</p>
                                </div>
                            </div>
                            
                            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                                <div class="flex flex-col items-center">
                                    <div class="inline-flex p-3 rounded-full bg-primary-100 text-primary-600 dark:bg-primary-700/20 dark:text-primary-400 mb-4">
                                        <x-heroicon-o-clock class="h-6 w-6"/>
                                    </div>
                                    <h3 class="text-lg font-medium text-center">{{ __('Attendance') }}</h3>
                                    <span class="text-3xl font-bold mt-2">{{ $details['attendance']['percentage'] ?? 0 }}%</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $details['attendance']['days_present'] ?? 0 }}/{{ $details['attendance']['days_total'] ?? 0 }} {{ __('Present Days') }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                                <div class="flex flex-col items-center">
                                    <div class="inline-flex p-3 rounded-full bg-warning-100 text-warning-600 dark:bg-warning-700/20 dark:text-warning-400 mb-4">
                                        <x-heroicon-o-clipboard-document-list class="h-6 w-6"/>
                                    </div>
                                    <h3 class="text-lg font-medium text-center">{{ __('Assignments') }}</h3>
                                    <span class="text-3xl font-bold mt-2">{{ $details['academics']['submitted_activities'] ?? 0 }}/{{ $details['academics']['total_activities'] ?? 0 }}</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Submitted') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Student Information Card -->
                        <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium">{{ __('Student Information') }}</h3>
                            </div>
                            <div class="p-6 grid md:grid-cols-2 gap-6">
                                <div>
                                    <dl class="space-y-4">
                                        <div class="flex flex-col sm:flex-row sm:justify-between">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Full Name') }}</dt>
                                            <dd class="mt-1 sm:mt-0 text-sm text-gray-900 dark:text-white">{{ $details['student']->name }}</dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between border-t border-gray-100 dark:border-gray-800 pt-4">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Student ID') }}</dt>
                                            <dd class="mt-1 sm:mt-0 text-sm text-gray-900 dark:text-white">{{ $details['student']->student_id }}</dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between border-t border-gray-100 dark:border-gray-800 pt-4">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
                                            <dd class="mt-1 sm:mt-0 text-sm text-gray-900 dark:text-white">{{ $details['student']->email }}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <div>
                                    <dl class="space-y-4">
                                        <div class="flex flex-col sm:flex-row sm:justify-between">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                                            <dd class="mt-1 sm:mt-0">
                                                <span class="inline-flex items-center text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300 px-2.5 py-0.5 rounded-full">
                                                    {{ $details['student']->status ?? 'Active' }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between border-t border-gray-100 dark:border-gray-800 pt-4">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Gender') }}</dt>
                                            <dd class="mt-1 sm:mt-0 text-sm text-gray-900 dark:text-white">{{ $details['student']->gender ?? 'Not specified' }}</dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between border-t border-gray-100 dark:border-gray-800 pt-4">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Phone') }}</dt>
                                            <dd class="mt-1 sm:mt-0 text-sm text-gray-900 dark:text-white">{{ $details['student']->phone ?? 'Not provided' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Submissions -->
                        <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-xl overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-medium">{{ __('Recent Submissions') }}</h3>
                            </div>
                            <div class="overflow-x-auto">
                                @if(count($details['academics']['recent_submissions']) > 0)
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Activity') }}</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Submitted') }}</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Score') }}</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach($details['academics']['recent_submissions'] as $submission)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $submission['activity_title'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $submission['submitted_at'] }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @if($submission['score'] !== null)
                                                            <div class="text-sm text-gray-900 dark:text-white">
                                                                {{ $submission['score'] }}/{{ $submission['total_points'] }}
                                                                @if($submission['total_points'] > 0)
                                                                    ({{ round(($submission['score'] / $submission['total_points']) * 100) }}%)
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('Not graded') }}</div>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @php
                                                            $statusColor = match($submission['status']) {
                                                                'completed' => 'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300',
                                                                'submitted' => 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300',
                                                                'late' => 'bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300',
                                                                'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                                                default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                                            };
                                                        @endphp
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                                            {{ __(ucfirst($submission['status'])) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="p-6 text-center">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No submissions found.') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Recent Activities and Academic Progress -->
                        <div class="grid gap-6 md:grid-cols-2 mt-6">
                            <!-- Recent Activities -->
                            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium">{{ __('Recent Activities') }}</h3>
                                    <a href="#" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">{{ __('View all') }}</a>
                                </div>
                                <div class="space-y-4">
                                    @forelse($details['recent_activities'] as $activity)
                                        <div class="flex gap-4">
                                            <div class="mt-1 flex-shrink-0">
                                                @if($activity['status'] === 'success')
                                                    <div class="w-2 h-2 rounded-full bg-success-500"></div>
                                                @elseif($activity['status'] === 'warning')
                                                    <div class="w-2 h-2 rounded-full bg-warning-500"></div>
                                                @elseif($activity['status'] === 'danger')
                                                    <div class="w-2 h-2 rounded-full bg-danger-500"></div>
                                                @else
                                                    <div class="w-2 h-2 rounded-full bg-primary-500"></div>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-medium">{{ $activity['title'] }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $activity['description'] }}</p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $activity['date'] }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-center py-4">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No recent activities found.') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Academic Progress -->
                            <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                                <h3 class="text-lg font-medium mb-4">{{ __('Academic Progress') }}</h3>
                                
                                <!-- Attendance Progress -->
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Attendance') }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $details['attendance']['percentage'] ?? 0 }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-primary-600 dark:bg-primary-500 h-2 rounded-full" style="width: {{ $details['attendance']['percentage'] ?? 0 }}%"></div>
                                    </div>
                                    <div class="mt-2 grid grid-cols-4 gap-1 text-center text-xs">
                                        <div class="text-success-600 dark:text-success-400">
                                            <div class="font-medium">{{ $details['attendance']['days_present'] ?? 0 }}</div>
                                            <div class="text-gray-500 dark:text-gray-400">{{ __('Present') }}</div>
                                        </div>
                                        <div class="text-warning-600 dark:text-warning-400">
                                            <div class="font-medium">{{ $details['attendance']['late_days'] ?? 0 }}</div>
                                            <div class="text-gray-500 dark:text-gray-400">{{ __('Late') }}</div>
                                        </div>
                                        <div class="text-danger-600 dark:text-danger-400">
                                            <div class="font-medium">{{ $details['attendance']['absent_days'] ?? 0 }}</div>
                                            <div class="text-gray-500 dark:text-gray-400">{{ __('Absent') }}</div>
                                        </div>
                                        <div class="text-primary-600 dark:text-primary-400">
                                            <div class="font-medium">{{ $details['attendance']['excused_days'] ?? 0 }}</div>
                                            <div class="text-gray-500 dark:text-gray-400">{{ __('Excused') }}</div>
                                        </div>
                                    </div>
                                    @if(isset($details['attendance']['last_absent']) && $details['attendance']['last_absent'])
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            {{ __('Last absence') }}: {{ $details['attendance']['last_absent'] }}
                                        </p>
                                    @endif
                                </div>
                                
                                <!-- Assignments Progress -->
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Assignments Completion') }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            @php
                                                $total = $details['academics']['total_activities'] ?? 0;
                                                $submitted = $details['academics']['submitted_activities'] ?? 0;
                                                $percentage = $total > 0 ? round(($submitted / $total) * 100) : 0;
                                            @endphp
                                            {{ $percentage }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-warning-500 dark:bg-warning-400 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                                
                                <!-- Upcoming Activities -->
                                <div class="mt-6">
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Upcoming Activities') }}</h4>
                                    @if(isset($details['academics']['upcoming_activities']) && count($details['academics']['upcoming_activities']) > 0)
                                        <div class="space-y-3">
                                            @foreach($details['academics']['upcoming_activities'] as $activity)
                                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="bg-warning-100 dark:bg-warning-900/30 p-2 rounded-lg">
                                                            <x-heroicon-o-clipboard-document class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                                                        </div>
                                                        <div>
                                                            <p class="font-medium text-sm">{{ $activity['title'] ?? 'Unnamed Activity' }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ __('Due') }}: {{ $activity['due_date'] ?? 'No date' }} 
                                                                @if(isset($activity['days_remaining']))
                                                                    ({{ $activity['days_remaining'] }} {{ Str::plural('day', $activity['days_remaining']) }} {{ __('remaining') }})
                                                                @endif
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3 text-center">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No upcoming activities at this time.') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Class Information -->
                        <div class="mt-6 bg-white dark:bg-gray-800 shadow rounded-xl p-6">
                            <h3 class="text-lg font-medium mb-4">{{ __('Class Information') }}</h3>
                            <div class="space-y-6">
                                <!-- Class Details -->
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="bg-primary-100 dark:bg-primary-900/30 p-2 rounded-lg">
                                                <x-heroicon-o-academic-cap class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                                            </div>
                                            <h4 class="font-medium">{{ __('Class Details') }}</h4>
                                        </div>
                                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 mt-2 text-sm">
                                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Class Name') }}:</dt>
                                            <dd class="text-gray-900 dark:text-white">{{ $details['team']->name ?? 'N/A' }}</dd>
                                            
                                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Join Code') }}:</dt>
                                            <dd class="text-gray-900 dark:text-white font-mono">{{ $details['team']->join_code ?? 'N/A' }}</dd>
                                            
                                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Grading System') }}:</dt>
                                            <dd class="text-gray-900 dark:text-white">{{ $details['team']->gradingSystemDescription ?? 'N/A' }}</dd>
                                        </dl>
                                    </div>
                                    
                                    <!-- Grading Information -->
                                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="bg-warning-100 dark:bg-warning-900/30 p-2 rounded-lg">
                                                <x-heroicon-o-chart-bar class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                                            </div>
                                            <h4 class="font-medium">{{ __('Grading Weights') }}</h4>
                                        </div>
                                        
                                        @if(isset($details['team']) && $details['team']->usesShsGrading())
                                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 mt-2 text-sm">
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Written Work') }}:</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ $details['team']->shs_ww_weight ?? '0' }}%</dd>
                                                
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Performance Tasks') }}:</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ $details['team']->shs_pt_weight ?? '0' }}%</dd>
                                                
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Quarterly Assessment') }}:</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ $details['team']->shs_qa_weight ?? '0' }}%</dd>
                                            </dl>
                                        @elseif(isset($details['team']) && $details['team']->usesCollegeGrading())
                                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 mt-2 text-sm">
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Prelim') }}:</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ $details['team']->college_prelim_weight ?? '0' }}%</dd>
                                                
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Midterm') }}:</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ $details['team']->college_midterm_weight ?? '0' }}%</dd>
                                                
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Final') }}:</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ $details['team']->college_final_weight ?? '0' }}%</dd>
                                                
                                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Scale') }}:</dt>
                                                <dd class="text-gray-900 dark:text-white">{{ $details['team']->getCollegeNumericScale() ?? 'N/A' }}</dd>
                                            </dl>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ __('Grading system not configured.') }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Contact Teachers -->
                                <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="bg-info-100 dark:bg-info-900/30 p-2 rounded-lg">
                                            <x-heroicon-o-user-group class="h-5 w-5 text-info-600 dark:text-info-400" />
                                        </div>
                                        <h4 class="font-medium">{{ __('Contact Teacher') }}</h4>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 p-2 bg-white dark:bg-gray-800 rounded-lg">
                                        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                            <span class="text-sm font-medium">
                                                {{ isset($details['team']->owner) ? strtoupper(substr($details['team']->owner->name ?? 'T', 0, 2)) : 'T' }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-medium">{{ $details['team']->owner->name ?? __('Teacher') }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $details['team']->owner->email ?? __('No email available') }}</p>
                                        </div>
                                        <div class="ml-auto flex gap-2">
                                            <button class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 p-1.5 rounded-md">
                                                <x-heroicon-m-envelope class="h-4 w-4" />
                                            </button>
                                            <button class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 p-1.5 rounded-md">
                                                <x-heroicon-m-chat-bubble-left-right class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
