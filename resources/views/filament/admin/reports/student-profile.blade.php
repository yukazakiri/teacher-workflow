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
                    <dt class="font-medium text-gray-600">Gender:</dt>
                    <dd>{{ ucfirst($reportData['student']['gender']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Birth Date:</dt>
                    <dd>{{ $reportData['student']['birth_date'] ? \Carbon\Carbon::parse($reportData['student']['birth_date'])->format('M d, Y') : 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Status:</dt>
                    <dd>{{ ucfirst($reportData['student']['status']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Phone:</dt>
                    <dd>{{ $reportData['student']['phone'] ?? 'N/A' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-3">Statistics</h3>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Total Activities:</dt>
                    <dd>{{ count($reportData['activitySubmissions']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Total Exams:</dt>
                    <dd>{{ count($reportData['examSubmissions']) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="font-medium text-gray-600">Attendance Records:</dt>
                    <dd>{{ count($reportData['attendances']) }}</dd>
                </div>
                @if(count($reportData['activitySubmissions']) > 0)
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600">Average Activity Score:</dt>
                        <dd>{{ number_format(collect($reportData['activitySubmissions'])->avg('score'), 2) }}</dd>
                    </div>
                @endif
                @if(count($reportData['examSubmissions']) > 0)
                    <div class="flex justify-between">
                        <dt class="font-medium text-gray-600">Average Exam Score:</dt>
                        <dd>{{ number_format(collect($reportData['examSubmissions'])->avg('score'), 2) }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="bg-white overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg font-medium">Attendance Records</h3>
        </div>
        <div class="border-t border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportData['attendances'] as $attendance)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($attendance['date'])->format('M d, Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($attendance['status']) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attendance['notes'] ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No attendance records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportData['activitySubmissions'] as $submission)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $submission['activity_id'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($submission['submitted_at'])->format('M d, Y g:i A') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $submission['score'] ?? 'Not graded' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($submission['status']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No activity submissions found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Exam Submissions -->
    <div class="bg-white overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:px-6 bg-gray-50">
            <h3 class="text-lg font-medium">Exam Submissions</h3>
        </div>
        <div class="border-t border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportData['examSubmissions'] as $submission)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $submission['exam_id'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($submission['submitted_at'])->format('M d, Y g:i A') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $submission['score'] ?? 'Not graded' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($submission['status']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No exam submissions found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>