<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ Auth::user()->currentTeam->name }} - Class Schedule</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Manage your class schedule for {{ Auth::user()->currentTeam->name }}.
                    </p>
                </div>
            </div>
        </div>

        {{-- Schedule Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            {{ $this->table }}
        </div>

        {{-- Weekly Calendar View --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Weekly Calendar View</h3>
            </div>
            
            <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700">
                @foreach($weekdays as $day)
                    <div class="bg-white dark:bg-gray-800">
                        <div class="py-2 px-3 text-center border-b border-gray-200 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $day }}</h4>
                        </div>
                        <div class="p-3 space-y-2 min-h-[250px]">
                            @php
                                $dayItems = $this->getScheduleItemsByDay($day);
                            @endphp
                            
                            @if($dayItems->isEmpty())
                                <div class="flex items-center justify-center h-full text-gray-400 text-sm">
                                    No classes scheduled
                                </div>
                            @else
                                @foreach($dayItems as $item)
                                    <div class="p-2 rounded-md text-sm" style="background-color: {{ $item->color ?? '#4f46e5' }}; color: white;">
                                        <div class="font-medium">{{ $item->title }}</div>
                                        <div class="text-xs mt-1">
                                            {{ \Carbon\Carbon::parse($item->start_time)->format('g:i A') }} - 
                                            {{ \Carbon\Carbon::parse($item->end_time)->format('g:i A') }}
                                        </div>
                                        @if($item->location)
                                            <div class="text-xs mt-1">
                                                <span class="opacity-75">Location:</span> {{ $item->location }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Instructions Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">How to Use</h3>
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </span>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Adding Classes</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Click the "New ScheduleItem" button to add a new class to your schedule. Select the day, set the start and end times, and provide a class name.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-success-100 text-success-600 dark:bg-success-900 dark:text-success-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </span>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Viewing Your Schedule</h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Your weekly class schedule is displayed in both table format and weekly calendar view. Classes are color-coded for easier recognition.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page> 