<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} - Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 24px;
            margin: 0;
            color: #2d3748;
        }
        h2 {
            font-size: 18px;
            color: #4a5568;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .meta {
            margin: 15px 0;
            color: #718096;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f8fafc;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .info-block {
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            width: 180px;
            font-weight: 600;
        }
        .info-value {
            flex: 1;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            text-align: center;
            color: #718096;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            width: calc(25% - 15px);
            box-sizing: border-box;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #4a5568;
        }
        .stat-label {
            font-size: 12px;
            color: #718096;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }} - Report</h1>
        <div class="meta">
            @if($reportType === 'student_profile')
                Student Profile: {{ $reportData['student']['name'] }}
            @elseif($reportType === 'activity_report')
                Activity Report: {{ $reportData['activity']['title'] }}
            @elseif($reportType === 'student_activities')
                Student Activities: {{ $reportData['student']['name'] }}
            @endif
            
            <br>
            Generated: {{ now()->format('F j, Y - g:i A') }}
        </div>
    </div>

    @if($reportType === 'student_profile')
        <h2>Student Information</h2>
        <div class="info-block">
            <div class="info-item">
                <div class="info-label">Name:</div>
                <div class="info-value">{{ $reportData['student']['name'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Student ID:</div>
                <div class="info-value">{{ $reportData['student']['student_id'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $reportData['student']['email'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst($reportData['student']['status']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Phone:</div>
                <div class="info-value">{{ $reportData['student']['phone'] ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Gender:</div>
                <div class="info-value">{{ ucfirst($reportData['student']['gender']) }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Birth Date:</div>
                <div class="info-value">{{ $reportData['student']['birth_date'] ?? 'N/A' }}</div>
            </div>
        </div>

        <h2>Attendance Summary</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['attendances'] as $attendance)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($attendance['date'])->format('M d, Y') }}</td>
                        <td>{{ ucfirst($attendance['status']) }}</td>
                        <td>{{ $attendance['notes'] ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center;">No attendance records found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <h2>Activity Submissions</h2>
        <table>
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Submitted</th>
                    <th>Score</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['activitySubmissions'] as $submission)
                    <tr>
                        <td>{{ $submission['activity_id'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($submission['submitted_at'])->format('M d, Y g:i A') }}</td>
                        <td>{{ $submission['score'] ?? 'Not graded' }}</td>
                        <td>{{ ucfirst($submission['status']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">No activity submissions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($reportType === 'activity_report')
        <h2>Activity Information</h2>
        <div class="info-block">
            <div class="info-item">
                <div class="info-label">Title:</div>
                <div class="info-value">{{ $reportData['activity']['title'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Description:</div>
                <div class="info-value">{{ $reportData['activity']['description'] ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Total Points:</div>
                <div class="info-value">{{ $reportData['activity']['total_points'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Due Date:</div>
                <div class="info-value">{{ $reportData['activity']['due_date'] ? \Carbon\Carbon::parse($reportData['activity']['due_date'])->format('M d, Y g:i A') : 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Teacher:</div>
                <div class="info-value">{{ $reportData['teacher']['name'] ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status:</div>
                <div class="info-value">{{ ucfirst($reportData['activity']['status']) }}</div>
            </div>
        </div>

        <h2>Submission Statistics</h2>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">{{ $reportData['submissionCount'] }}</div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($reportData['averageScore'], 2) }}</div>
                <div class="stat-label">Average Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $reportData['maxScore'] }}</div>
                <div class="stat-label">Highest Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $reportData['minScore'] }}</div>
                <div class="stat-label">Lowest Score</div>
            </div>
        </div>

        <h2>Student Submissions</h2>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Submitted Date</th>
                    <th>Score</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['submissions'] as $submission)
                    <tr>
                        <td>{{ $submission['student_id'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($submission['submitted_at'])->format('M d, Y g:i A') }}</td>
                        <td>{{ $submission['score'] ?? 'Not graded' }}</td>
                        <td>{{ ucfirst($submission['status']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">No submissions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif($reportType === 'student_activities')
        <h2>Student Information</h2>
        <div class="info-block">
            <div class="info-item">
                <div class="info-label">Name:</div>
                <div class="info-value">{{ $reportData['student']['name'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Student ID:</div>
                <div class="info-value">{{ $reportData['student']['student_id'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $reportData['student']['email'] }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Date Range:</div>
                <div class="info-value">
                    {{ $reportData['dateRange']['start'] ? \Carbon\Carbon::parse($reportData['dateRange']['start'])->format('M d, Y') : 'All time' }}
                    to
                    {{ $reportData['dateRange']['end'] ? \Carbon\Carbon::parse($reportData['dateRange']['end'])->format('M d, Y') : 'Present' }}
                </div>
            </div>
        </div>

        <h2>Activity Statistics</h2>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">{{ $reportData['submissionCount'] }}</div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ number_format($reportData['averageScore'], 2) }}</div>
                <div class="stat-label">Average Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">{{ $reportData['activitiesCompleted'] }}</div>
                <div class="stat-label">Activities Completed</div>
            </div>
        </div>

        <h2>Activity Submissions</h2>
        <table>
            <thead>
                <tr>
                    <th>Activity</th>
                    <th>Submitted Date</th>
                    <th>Score</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['submissions'] as $submission)
                    <tr>
                        <td>{{ $submission['activity']['title'] ?? $submission['activity_id'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($submission['submitted_at'])->format('M d, Y g:i A') }}</td>
                        <td>{{ $submission['score'] ?? 'Not graded' }}</td>
                        <td>{{ ucfirst($submission['status']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center;">No submissions found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ config('app.name') }} &copy; {{ date('Y') }} | Generated on {{ now()->format('F j, Y - g:i A') }}
    </div>
</body>
</html>