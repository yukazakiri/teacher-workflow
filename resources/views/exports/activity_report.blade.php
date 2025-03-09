<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Activity Report: {{ $activity->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
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
        h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        h2 {
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .summary {
            margin-bottom: 30px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .stat {
            margin-bottom: 10px;
        }
        .stat-label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-completed {
            color: #4CAF50;
        }
        .status-in-progress {
            color: #FFC107;
        }
        .status-not-started {
            color: #F44336;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Activity Report: {{ $activity->title }}</h1>
        <div>{{ ucfirst($activity->mode) }} {{ ucfirst($activity->category) }} Activity</div>
        <div>Format: {{ ucfirst($activity->format) }}{{ $activity->format === 'other' ? " ({$activity->custom_format})" : '' }}</div>
    </div>

    <div class="summary">
        <h2>Summary</h2>
        <div class="stat">
            <span class="stat-label">Total Students:</span> {{ $totalStudents }}
        </div>
        <div class="stat">
            <span class="stat-label">Completed Submissions:</span> {{ $completedSubmissions }}
        </div>
        <div class="stat">
            <span class="stat-label">Completion Rate:</span> {{ number_format($completionRate, 1) }}%
        </div>
        <div class="stat">
            <span class="stat-label">Average Score:</span> {{ number_format($averageScore, 1) }} / {{ $activity->total_points }}
        </div>
        <div class="stat">
            <span class="stat-label">Deadline:</span> {{ $activity->deadline ? $activity->deadline->format('F j, Y, g:i a') : 'No deadline set' }}
        </div>
    </div>

    <h2>Student Submissions</h2>
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Status</th>
                <th>Score</th>
                <th>Submission Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($submissions as $submission)
            <tr>
                <td>{{ $submission->student->name }}</td>
                <td class="status-{{ $submission->status }}">{{ ucfirst($submission->status) }}</td>
                <td>{{ $submission->score ?? 'N/A' }} / {{ $activity->total_points }}</td>
                <td>{{ $submission->created_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @endforeach
            
            @php
                $submittedStudentIds = $submissions->pluck('student_id')->toArray();
            @endphp
            
            @foreach($activity->team->allUsers() as $user)
                @if($user->id !== $activity->teacher_id && !in_array($user->id, $submittedStudentIds))
                <tr>
                    <td>{{ $user->name }}</td>
                    <td class="status-not-started">Not Started</td>
                    <td>N/A</td>
                    <td>N/A</td>
                </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('F j, Y, g:i a') }}
    </div>
</body>
</html>
