<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Activity Progress') }}: {{ $activity->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">{{ $activity->title }}</h3>
                        <div class="mt-2 text-sm text-gray-600">
                            {!! $activity->description !!}
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center">
                            <div class="text-sm text-gray-600">
                                <span class="font-medium">Type:</span> {{ $activity->activityType->name }}
                            </div>
                            <div class="ml-4 text-sm text-gray-600">
                                <span class="font-medium">Format:</span> 
                                {{ $activity->format === 'other' ? $activity->custom_format : ucfirst($activity->format) }}
                            </div>
                            <div class="ml-4 text-sm text-gray-600">
                                <span class="font-medium">Mode:</span> 
                                {{ ucfirst($activity->mode) }}
                            </div>
                            <div class="ml-4 text-sm text-gray-600">
                                <span class="font-medium">Total Points:</span> {{ $activity->total_points }}
                            </div>
                            @if ($activity->deadline)
                                <div class="ml-4 text-sm text-gray-600">
                                    <span class="font-medium">Deadline:</span> {{ $activity->deadline->format('F j, Y, g:i a') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900">Submission Progress</h4>
                        
                        @if ($activity->isGroupActivity())
                            <div class="mt-4">
                                <h5 class="text-sm font-medium text-gray-700">Group Submissions</h5>
                                <div class="mt-2 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted At</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @forelse ($activity->groups as $group)
                                                @php
                                                    $submission = $activity->submissions()->where('group_id', $group->id)->first();
                                                @endphp
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $group->name }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        @if ($submission)
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                {{ $submission->isSubmitted() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                                {{ ucfirst($submission->status) }}
                                                            </span>
                                                        @else
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                                Not Started
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $submission && $submission->submitted_at ? $submission->submitted_at->format('F j, Y, g:i a') : 'N/A' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $submission && $submission->isGraded() ? $submission->score . ' / ' . $activity->total_points : 'Not Graded' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        @if ($submission)
                                                            <a href="#" class="text-indigo-600 hover:text-indigo-900">View</a>
                                                            @if ($submission->isSubmitted() && !$submission->isGraded())
                                                                <a href="#" class="ml-3 text-green-600 hover:text-green-900">Grade</a>
                                                            @endif
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No groups have been created for this activity.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="mt-4">
                                <h5 class="text-sm font-medium text-gray-700">Individual Submissions</h5>
                                <div class="mt-2 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted At</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @forelse ($activity->submissions as $submission)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {{ $submission->student->user->name }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                            {{ $submission->isSubmitted() ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                            {{ ucfirst($submission->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $submission->submitted_at ? $submission->submitted_at->format('F j, Y, g:i a') : 'N/A' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $submission->isGraded() ? $submission->score . ' / ' . $activity->total_points : 'Not Graded' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <a href="#" class="text-indigo-600 hover:text-indigo-900">View</a>
                                                        @if ($submission->isSubmitted() && !$submission->isGraded())
                                                            <a href="#" class="ml-3 text-green-600 hover:text-green-900">Grade</a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No submissions yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
