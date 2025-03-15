<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Submit Activity') }}: {{ $activity->title }}
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
                        <h4 class="text-md font-medium text-gray-900">Instructions</h4>
                        <div class="mt-2 text-sm text-gray-600">
                            {!! $activity->instructions !!}
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
                                <span class="font-medium">Total Points:</span> {{ $activity->total_points }}
                            </div>
                            @if ($activity->deadline)
                                <div class="ml-4 text-sm text-gray-600">
                                    <span class="font-medium">Deadline:</span> {{ $activity->deadline->format('F j, Y, g:i a') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <form action="{{ route('activities.submit.store', $activity) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-6">
                            <label for="content" class="block text-sm font-medium text-gray-700">Your Answer</label>
                            <div class="mt-1">
                                <textarea id="content" name="content" rows="5" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ $submission->content ?? old('content') }}</textarea>
                            </div>
                            @error('content')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="attachments" class="block text-sm font-medium text-gray-700">Attachments</label>
                            <div class="mt-1">
                                <input id="attachments" name="attachments[]" type="file" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            </div>
                            <p class="mt-1 text-sm text-gray-500">You can upload multiple files (max 10MB each)</p>
                            @error('attachments')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('attachments.*')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if ($submission && !empty($submission->attachments))
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-700">Current Attachments</h4>
                                <ul class="mt-2 divide-y divide-gray-200">
                                    @foreach ($submission->attachments as $index => $attachment)
                                        <li class="py-2 flex justify-between">
                                            <div class="flex items-center">
                                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="ml-2 text-sm text-gray-600">{{ $attachment['name'] }}</span>
                                            </div>
                                            <div class="flex items-center">
                                                <a href="{{ Storage::url($attachment['path']) }}" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-900 mr-4">View</a>
                                                @if (!$submission->isGraded())
                                                    <form action="{{ route('submissions.attachments.delete', [$submission, $index]) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm text-red-600 hover:text-red-900">Delete</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex justify-end space-x-3">
                            <input type="hidden" name="status" value="draft" id="status-field">
                            <button type="button" onclick="document.getElementById('status-field').value='draft'; this.form.submit();" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Save as Draft
                            </button>
                            <button type="button" onclick="document.getElementById('status-field').value='submitted'; this.form.submit();" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 