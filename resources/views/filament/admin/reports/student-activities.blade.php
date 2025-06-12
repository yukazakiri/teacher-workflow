<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-3">Student Information</h3>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Name:</dt>
                    <dd>{{ $reportData['student']['name'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Student ID:</dt>
                    <dd>{{ $reportData['student']['student_id'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Email:</dt>
                    <dd>{{ $reportData['student']['email'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Status:</dt>
                    <dd>{{ ucfirst($reportData['student']['status']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Date Range:</dt>
                    <dd>
                        {{ $reportData['dateRange']['start'] ? \Carbon\Carbon::parse($reportData['dateRange']['start'])->format('M d, Y') : 'All time' }}
                        to
                        {{ $reportData['dateRange']['end'] ? \Carbon\Carbon::parse($reportData['dateRange']['end'])->format('M d, Y') : 'Present' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-3">Performance Summary</h3>
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded border">
                    <div class="text-2xl font-bold">{{ $reportData['submissionCount'] }}</div>
                    <div class="text-sm text-gray-600">Total Submissions</div>
                </div>
                <div class="bg-white p-4 rounded border">
                    <div class="text-2xl font-bold">{{ number_format($reportData['averageScore'], 2) }}</div>
                    <div class="text-sm text-gray-600">Average Score</div>
                </div>
                <div class="bg-white p-4 rounded border">
                    <div class="text-2xl font-bold">{{ $reportData['activitiesCompleted'] }}</div>
                    <div class="text-sm text-gray-600">Activities Completed</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Submissions -->
    <div class="bg-white overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg font-medium">Activity Submissions</h3>
        </div>
        <div class="border-t border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportData['submissions'] as $submission)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $submission['activity']['title'] ?? $submission['activity_id'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($submission['submitted_at'])->format('M d, Y g:i A') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $submission['score'] ?? 'Not graded' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ ucfirst($submission['status']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No submissions found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($reportData['submissionCount'] > 0)
    <!-- Performance Trend Chart (placeholder) -->
    <div class="bg-white overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg font-medium">Performance Trend</h3>
        </div>
        <div class="p-6 flex justify-center">
            <div class="w-full h-64 bg-gray-100 rounded flex items-center justify-center">
                <p class="text-gray-500 text-center">Score Trend Over Time<br>(Visualization would appear here)</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Activity Completion Summary -->
    <div class="bg-white overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg font-medium">Activity Completion Summary</h3>
        </div>
        <div class="p-6">
            <div class="prose max-w-none">
                <p>Student has completed {{ $reportData['activitiesCompleted'] }} activities with an average score of {{ number_format($reportData['averageScore'], 2) }}.</p>
                
                @if($reportData['submissionCount'] > 0)
                    <div class="mt-4">
                        <h4>Score Distribution</h4>
                        <div class="grid grid-cols-5 gap-2 mt-2">
                            @php
                                $scoreRanges = [
                                    '90-100' => 0,
                                    '80-89' => 0,
                                    '70-79' => 0,
                                    '60-69' => 0,
                                    'Below 60' => 0,
                                ];
                                
                                foreach($reportData['submissions'] as $submission) {
                                    if (isset($submission['score'])) {
                                        $score = (float) $submission['score'];
                                        if ($score >= 90) {
                                            $scoreRanges['90-100']++;
                                        } elseif ($score >= 80) {
                                            $scoreRanges['80-89']++;
                                        } elseif ($score >= 70) {
                                            $scoreRanges['70-79']++;
                                        } elseif ($score >= 60) {
                                            $scoreRanges['60-69']++;
                                        } else {
                                            $scoreRanges['Below 60']++;
                                        }
                                    }
                                }
                            @endphp
                            
                            @foreach($scoreRanges as $range => $count)
                                <div class="bg-gray-50 p-2 rounded border text-center">
                                    <div class="text-lg font-bold">{{ $count }}</div>
                                    <div class="text-xs text-gray-600">{{ $range }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>