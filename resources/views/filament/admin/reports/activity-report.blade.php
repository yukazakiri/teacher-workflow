<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-3">Activity Information</h3>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Title:</dt>
                    <dd>{{ $reportData['activity']['title'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Description:</dt>
                    <dd class="text-right">{{ Str::limit($reportData['activity']['description'] ?? 'N/A', 50) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Component Type:</dt>
                    <dd>{{ $reportData['activity']['component_type'] ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Total Points:</dt>
                    <dd>{{ $reportData['activity']['total_points'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Due Date:</dt>
                    <dd>{{ $reportData['activity']['due_date'] ? \Carbon\Carbon::parse($reportData['activity']['due_date'])->format('M d, Y g:i A') : 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Status:</dt>
                    <dd>{{ ucfirst($reportData['activity']['status']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Teacher:</dt>
                    <dd>{{ $reportData['teacher']['name'] ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-3">Submission Statistics</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white p-4 rounded border">
                    <div class="text-2xl font-bold">{{ $reportData['submissionCount'] }}</div>
                    <div class="text-sm text-gray-600">Total Submissions</div>
                </div>
                <div class="bg-white p-4 rounded border">
                    <div class="text-2xl font-bold">{{ number_format($reportData['averageScore'], 2) }}</div>
                    <div class="text-sm text-gray-600">Average Score</div>
                </div>
                <div class="bg-white p-4 rounded border">
                    <div class="text-2xl font-bold">{{ $reportData['maxScore'] }}</div>
                    <div class="text-sm text-gray-600">Highest Score</div>
                </div>
                <div class="bg-white p-4 rounded border">
                    <div class="text-2xl font-bold">{{ $reportData['minScore'] }}</div>
                    <div class="text-sm text-gray-600">Lowest Score</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Submissions -->
    <div class="bg-white overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg font-medium">Student Submissions</h3>
        </div>
        <div class="border-t border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportData['submissions'] as $submission)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $submission['student_id'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($submission['submitted_at'])->format('M d, Y g:i A') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $submission['score'] ?? 'Not graded' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($submission['status']) }}</td>
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
    <!-- Performance Distribution Chart (placeholder) -->
    <div class="bg-white overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg font-medium">Performance Distribution</h3>
        </div>
        <div class="p-6 flex justify-center">
            <div class="w-full h-64 bg-gray-100 rounded flex items-center justify-center">
                <p class="text-gray-500 text-center">Score Distribution Chart<br>(Visualization would appear here)</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Activity Details -->
    <div class="bg-white overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg font-medium">Activity Details</h3>
        </div>
        <div class="p-6">
            <div class="prose max-w-none">
                <h4>Instructions</h4>
                <div>{{ $reportData['activity']['instructions'] ?? 'No instructions provided.' }}</div>

                @if(!empty($reportData['activity']['grading_criteria']))
                <h4 class="mt-4">Grading Criteria</h4>
                <div>
                    @if(is_array($reportData['activity']['grading_criteria']))
                        <ul>
                            @foreach($reportData['activity']['grading_criteria'] as $criterion)
                                <li>{{ $criterion }}</li>
                            @endforeach
                        </ul>
                    @else
                        {{ $reportData['activity']['grading_criteria'] }}
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>