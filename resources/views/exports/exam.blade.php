<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $exam->title }}</title>
    <style>
        body {font-family:Arial,sans-serif;line-height:1.2;font-size:11px;margin:0;padding:10px;}
        .exam-header {text-align:center;margin-bottom:10px;border-bottom:1px solid #000;padding-bottom:5px;}
        .school-name {font-weight:bold;text-transform:uppercase;}
        h1 {font-size:14px;margin:5px 0;text-transform:uppercase;}
        .exam-info, .student-info, .instructions {border:1px solid #000;padding:5px;margin-bottom:10px;font-size:10px;}
        .exam-meta, .student-info-table {width:100%;border-collapse:collapse;}
        .exam-meta td, .student-info-table td {padding:2px;}
        .exam-meta td:first-child, .student-info-table .label {font-weight:bold;width:30%;}
        .student-info-table .value {border-bottom:1px solid #000;}
        .section-title {font-weight:bold;text-decoration:underline;margin:5px 0;}
        .question {margin-bottom:10px;page-break-inside:avoid;padding-left:5px;}
        .question-header {font-weight:bold;margin-bottom:3px;}
        .question-content {margin-bottom:5px;}
        .options {margin-left:10px;}
        .option {margin-bottom:2px;}
        .answer-box {border:1px solid #000;min-height:30px;margin-top:3px;}
        .answer {margin-top:3px;padding:2px;background-color:#f0f0f0;border:1px dashed #999;}
        .footer {margin-top:10px;text-align:center;font-size:9px;border-top:1px solid #000;padding-top:5px;}
        .watermark {position:fixed;bottom:5px;right:5px;opacity:0.1;font-size:8px;transform:rotate(-45deg);}
        @page {margin:1cm;}
    </style>
</head>
<body>
    <div class="watermark">CONFIDENTIAL</div>

    <div class="exam-header">
        <div class="school-name">{{ $exam->team ? $exam->team->name : 'Academic Institution' }}</div>
        <h1>{{ $exam->title }}</h1>
    </div>

    <div class="student-info">
        <table class="student-info-table">
            <tr>
                <td class="label">Name:</td><td class="value">&nbsp;</td>
                <td class="label">Date:</td><td class="value">&nbsp;</td>
            </tr>
            <tr>
                <td class="label">ID:</td><td class="value">&nbsp;</td>
                <td class="label">Class:</td><td class="value">&nbsp;</td>
            </tr>
        </table>
    </div>



    @if($exam->description)
    <div class="instructions">
        <div class="section-title">INSTRUCTIONS</div>
        {!! $exam->description !!}
    </div>
    @endif

    <div class="questions-section">
        <div class="section-title">QUESTIONS</div>
        @foreach($exam->questions as $index => $question)
        <div class="question">
            <div class="question-content"> <span class="question-header">{{ $index + 1 }}. </span>{!! $question->content !!}</div>
            {{-- <div class="question-header">{{ $index + 1 }}. {!! $question->content !!}</div> --}}

            @if($question->options)
            <div class="options">
                @foreach(explode("\n", $question->options) as $option)
                <div class="option">{{ trim($option) }}</div>
                @endforeach
            </div>
            @endif

            @if($question->type == 'short_answer' || $question->type == 'essay')
            <div class="answer-box"></div>
            @endif

            @if($includeAnswerKey && $question->answer)
            <div class="answer"><strong>Answer:</strong> {{ $question->answer }}</div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="footer">
        <div>End of Exam - {{ now()->format('m/d/Y') }}</div>
    </div>
</body>
</html>
