<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $exam->title }}</title>
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
        .description {
            margin-bottom: 20px;
            font-style: italic;
        }
        .meta {
            margin-bottom: 20px;
            font-size: 14px;
        }
        .question {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .question-header {
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        .question-content {
            margin-bottom: 15px;
        }
        .options {
            margin-left: 20px;
        }
        .option {
            margin-bottom: 5px;
        }
        .answer {
            margin-top: 10px;
            padding: 5px;
            background-color: #f9f9f9;
            border-left: 3px solid #4CAF50;
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
        <h1>{{ $exam->title }}</h1>
        <div class="meta">
            <div>Total Points: {{ $exam->total_points }}</div>
            <div>Status: {{ ucfirst($exam->status) }}</div>
        </div>
    </div>

    @if($exam->description)
    <div class="description">
        {!! $exam->description !!}
    </div>
    @endif

    <div class="questions">
        @foreach($exam->questions as $index => $question)
        <div class="question">
            <div class="question-header">
                <div>Question {{ $index + 1 }}</div>
                <div>{{ $question->pivot->points }} points</div>
            </div>
            <div class="question-content">
                {!! $question->content !!}
            </div>
            
            @if($question->options)
            <div class="options">
                @foreach(explode("\n", $question->options) as $option)
                <div class="option">{{ trim($option) }}</div>
                @endforeach
            </div>
            @endif
            
            @if($includeAnswerKey && $question->answer)
            <div class="answer">
                <strong>Answer:</strong> {{ $question->answer }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="footer">
        Generated on {{ now()->format('F j, Y, g:i a') }}
    </div>
</body>
</html>
