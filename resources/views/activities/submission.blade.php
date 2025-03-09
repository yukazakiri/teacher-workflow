<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Submission Details') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('activities.progress', $activity) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    {{ __('Back to Progress') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Submission Info -->
                        <div class="md:col-span-2 space-y-6">
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-2">{{ __('Activity Information') }}</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Title') }}</p>
                                        <p class="font-medium">{{ $activity->title }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Type') }}</p>
                                        <p class="font-medium">{{ $activity->activityType->name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Format') }}</p>
                                        <p class="font-medium">{{ $activity->format }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total Points') }}</p>
                                        <p class="font-medium">{{ $activity->total_points }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-2">{{ __('Submission Content') }}</h3>
                                <div class="prose dark:prose-invert max-w-none mt-4">
                                    {!! $submission->content !!}
                                </div>
                            </div>

                            @if($submission->attachments && count($submission->attachments) > 0)
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-2">{{ __('Attachments') }}</h3>
                                <ul class="space-y-2">
                                    @foreach($submission->attachments as $attachment)
                                    <li class="flex items-center space-x-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd" />
                                        </svg>
                                        <a href="{{ Storage::url($attachment) }}" target="_blank" class="text-blue-500 hover:underline">
                                            {{ basename($attachment) }}
                                        </a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>

                        <!-- Student Info and Grading -->
                        <div class="space-y-6">
                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-2">{{ __('Student Information') }}</h3>
                                <div class="space-y-2">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Name') }}</p>
                                        <p class="font-medium">{{ $submission->student->name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Email') }}</p>
                                        <p class="font-medium">{{ $submission->student->email }}</p>
                                    </div>
                                    @if($activity->mode === 'group' && $submission->group)
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Group') }}</p>
                                        <p class="font-medium">{{ $submission->group->name }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-2">{{ __('Submission Status') }}</h3>
                                <div class="space-y-2">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Status') }}</p>
                                        <p class="font-medium">
                                            @if($submission->status === 'draft')
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">{{ __('Draft') }}</span>
                                            @elseif($submission->status === 'submitted')
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ __('Submitted') }}</span>
                                            @elseif($submission->status === 'completed')
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">{{ __('Completed') }}</span>
                                            @elseif($submission->status === 'late')
                                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">{{ __('Late') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Submitted At') }}</p>
                                        <p class="font-medium">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y h:i A') : 'Not submitted yet' }}</p>
                                    </div>
                                    @if($submission->isGraded())
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Graded At') }}</p>
                                        <p class="font-medium">{{ $submission->graded_at->format('M d, Y h:i A') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Graded By') }}</p>
                                        <p class="font-medium">{{ $submission->gradedBy->name }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                                <h3 class="text-lg font-semibold mb-2">{{ __('Grading') }}</h3>
                                @if($submission->isGraded())
                                    <div class="space-y-2">
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Score') }}</p>
                                            <p class="font-medium">{{ $submission->score }} / {{ $activity->total_points }}</p>
                                        </div>
                                        @if($submission->feedback)
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Feedback') }}</p>
                                            <div class="prose dark:prose-invert max-w-none mt-2">
                                                {!! $submission->feedback !!}
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                @else
                                    <form action="{{ route('activities.grade-submission', $submission) }}" method="POST">
                                        @csrf
                                        <div class="space-y-4">
                                            <div>
                                                <label for="score" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Score') }}</label>
                                                <div class="mt-1 flex rounded-md shadow-sm">
                                                    <input type="number" name="score" id="score" min="0" max="{{ $activity->total_points }}" class="focus:ring-indigo-500 focus:border-indigo-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="0">
                                                    <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-300 text-sm">
                                                        / {{ $activity->total_points }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div>
                                                <label for="feedback" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Feedback') }}</label>
                                                <div class="mt-1">
                                                    <textarea id="feedback" name="feedback" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                                                </div>
                                            </div>
                                            <div class="flex justify-end">
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                                                    {{ __('Submit Grade') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
