<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Activity Progress') }}: {{ $activity->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Activity Summary Card -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $activity->title }}</h3>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ ucfirst($activity->mode) }} {{ ucfirst($activity->category) }} Activity
                            </p>
                            <p class="mt-1 text-sm text-gray-600">
                                Format: {{ ucfirst($activity->format) }}{{ $activity->format === 'other' ? " ({$activity->custom_format})" : '' }}
                            </p>
                            <p class="mt-1 text-sm text-gray-600">
                                Total Points: {{ $activity->total_points }}
                            </p>
                            @if($activity->deadline)
                                <p class="mt-1 text-sm text-gray-600">
                                    Deadline: {{ $activity->deadline->format('F j, Y, g:i a') }}
                                </p>
                            @endif
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('filament.admin.resources.activities.edit', $activity) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                                {{ __('Edit Activity') }}
                            </a>
                            <form action="{{ route('activities.generate-report', $activity) }}" method="GET">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                                    {{ __('Generate Report') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Completion Rate') }}</h3>
                        <div class="flex items-center">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $completionRate }}%"></div>
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-700">{{ number_format($completionRate, 1) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Average Score') }}</h3>
                        <div class="text-3xl font-bold text-indigo-600">
                            {{ number_format($averageScore, 1) }} <span class="text-sm text-gray-500">/ {{ $activity->total_points }}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Submission Status') }}</h3>
                        <div class="flex justify-between text-sm">
                            <div>
                                <div class="text-green-600 font-medium">{{ count($studentsByStatus['completed']) }} Completed</div>
                                <div class="text-yellow-600 font-medium">{{ count($studentsByStatus['in_progress']) }} In Progress</div>
                                <div class="text-red-600 font-medium">{{ count($studentsByStatus['not_started']) }} Not Started</div>
                            </div>
                            <div class="text-right">
                                <div class="text-gray-700">Total: {{ count($studentsByStatus['completed']) + count($studentsByStatus['in_progress']) + count($studentsByStatus['not_started']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs for different status groups -->
            <div x-data="{ activeTab: 'completed' }" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button @click="activeTab = 'completed'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'completed', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'completed' }" class="py-4 px-6 border-b-2 font-medium text-sm">
                            {{ __('Completed') }} ({{ count($studentsByStatus['completed']) }})
                        </button>
                        <button @click="activeTab = 'in_progress'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'in_progress', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'in_progress' }" class="py-4 px-6 border-b-2 font-medium text-sm">
                            {{ __('In Progress') }} ({{ count($studentsByStatus['in_progress']) }})
                        </button>
                        <button @click="activeTab = 'not_started'" :class="{ 'border-indigo-500 text-indigo-600': activeTab === 'not_started', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'not_started' }" class="py-4 px-6 border-b-2 font-medium text-sm">
                            {{ __('Not Started') }} ({{ count($studentsByStatus['not_started']) }})
                        </button>
                    </nav>
                </div>

                <!-- Completed Students -->
                <div x-show="activeTab === 'completed'" class="p-6">
                    @if(count($studentsByStatus['completed']) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Student') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Score') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Submitted At') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($studentsByStatus['completed'] as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded-full" src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $item['user']->name }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ $item['user']->email }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $item['submission']->score }} / {{ $activity->total_points }}</div>
                                                <div class="text-sm text-gray-500">{{ number_format(($item['submission']->score / $activity->total_points) * 100, 1) }}%</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $item['submission']->created_at->format('F j, Y, g:i a') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('activities.view-submission', $item['submission']) }}" class="text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-gray-500">
                            {{ __('No completed submissions yet.') }}
                        </div>
                    @endif
                </div>

                <!-- In Progress Students -->
                <div x-show="activeTab === 'in_progress'" class="p-6" style="display: none;">
                    @if(count($studentsByStatus['in_progress']) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Student') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Progress') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Last Activity') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($studentsByStatus['in_progress'] as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded-full" src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $item['user']->name }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ $item['user']->email }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2">
                                                        <div class="bg-yellow-400 h-2.5 rounded-full" style="width: {{ $item['progress']->percentage }}%"></div>
                                                    </div>
                                                    <span class="text-sm text-gray-700">{{ $item['progress']->percentage }}%</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $item['progress']->updated_at->format('F j, Y, g:i a') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="#" class="text-indigo-600 hover:text-indigo-900">{{ __('Send Reminder') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-gray-500">
                            {{ __('No students in progress.') }}
                        </div>
                    @endif
                </div>

                <!-- Not Started Students -->
                <div x-show="activeTab === 'not_started'" class="p-6" style="display: none;">
                    @if(count($studentsByStatus['not_started']) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Student') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Status') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($studentsByStatus['not_started'] as $item)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded-full" src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ $item['user']->name }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            {{ $item['user']->email }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    {{ __('Not Started') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="#" class="text-indigo-600 hover:text-indigo-900">{{ __('Send Reminder') }}</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-gray-500">
                            {{ __('All students have started the activity.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
