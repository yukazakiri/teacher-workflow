@props(['submission'])

<div class="space-y-4 p-4">
    @if($submission)
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Submission Details</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Student: {{ $submission->student?->name ?? 'N/A' }} |
                Submitted: {{ $submission->submitted_at?->format('M d, Y H:i') ?? 'N/A' }}
            </p>
        </div>

        @if($submission->activity?->submission_type === 'form' && !empty($submission->form_data))
            <div class="prose prose-sm dark:prose-invert max-w-none rounded-md border border-gray-300 dark:border-gray-700 p-4">
                <h4>Form Responses</h4>
                <dl>
                    @foreach($submission->getFormattedFormData() as $field) 
                        <dt class="font-semibold">{{ $field['label'] }}</dt>
                        <dd class="ml-4 mb-2">
                            @if(is_array($field['value']))
                                {{ implode(', ', $field['value']) }}
                            @elseif(Str::startsWith($field['value'], ['http://', 'https://']))
                                <a href="{{ $field['value'] }}" target="_blank" class="text-primary-600 hover:underline">View Link</a>
                            @else
                                {!! nl2br(e($field['value'])) !!}
                            @endif
                        </dd>
                    @endforeach
                </dl>
            </div>
        @endif

        
         @if($submission->activity?->submission_type === 'resource' && !empty($submission->content))
             <div class="prose prose-sm dark:prose-invert max-w-none rounded-md border border-gray-300 dark:border-gray-700 p-4">
                 <h4>Text Submission</h4>
                 <div>{!! $submission->content !!}</div> 
             </div>
         @endif

     
         @if($submission->activity?->submission_type === 'resource' && !empty($submission->attachments))
             <div class="rounded-md border border-gray-300 dark:border-gray-700 p-4">
                 <h4 class="font-medium mb-2">Attached Files</h4>
                 <ul class="list-disc list-inside space-y-1">
                     @foreach($submission->attachments as $attachmentPath)
                        @php
                            
                            $filename = basename($attachmentPath);
                          
                            $url = Storage::disk('public')->url($attachmentPath);
                        @endphp
                         <li>
                             <a href="{{ $url }}" target="_blank" class="text-primary-600 hover:underline">
                                 {{ $filename }}
                             </a>
                         </li>
                     @endforeach
                 </ul>
             </div>
         @endif

    
        @if(!empty($submission->feedback))
            <div class="prose prose-sm dark:prose-invert max-w-none rounded-md border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
                 <h4 class="text-gray-700 dark:text-gray-300">Teacher Feedback</h4>
                 <div>{!! $submission->feedback !!}</div>
             </div>
        @else
             <p class="text-sm text-gray-500 italic">No feedback provided yet.</p>
         @endif

    @else
        <p class="text-center text-gray-500 dark:text-gray-400">No submission data available.</p>
    @endif
</div>
