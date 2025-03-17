<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $exam->title }}</title>
    <style>
        body {font-family:Arial,sans-serif;font-size:10pt;line-height:1.3;margin:0;padding:10px;}
        .header {text-align:center;border-bottom:1px solid #000;margin-bottom:10px;padding-bottom:5px;}
        .school {font-weight:bold;font-size:11pt;}
        h1 {font-size:12pt;margin:5px 0;text-transform:uppercase;}
        .info-box {border:1px solid #000;padding:5px;margin-bottom:10px;}
        .info-table {width:100%;border-collapse:collapse;}
        .info-table td {padding:2px;}
        .info-table .label {font-weight:bold;width:25%;}
        .info-table .value {border-bottom:1px solid #000;}
        .section {font-weight:bold;border-bottom:1px solid #999;margin:15px 0 5px 0;font-size:11pt;}
        .q {margin-bottom:12px;page-break-inside:avoid;}
        .q-header {font-weight:bold;margin-bottom:2px;display:flex;justify-content:space-between;}
        .q-points {font-style:italic;font-size:9pt;}
        .options {margin-left:15px;}
        .option {margin-bottom:4px;display:flex;align-items:flex-start;}
        .checkbox {display:inline-block;width:10px;height:10px;border:1px solid #000;margin-right:5px;margin-top:2px;}
        .answer-space {border:1px solid #000;min-height:30px;margin-top:3px;}
        .essay-space {border:1px solid #000;min-height:120px;margin-top:3px;}
        .match-container {display:flex;justify-content:space-between;margin-top:5px;}
        .match-col {width:48%;}
        .match-item {padding:3px;margin-bottom:3px;border:1px solid #ddd;}
        .blank {border-bottom:1px solid #000;display:inline-block;min-width:80px;height:12px;}
        .answer {margin-top:5px;padding:3px;background:#f5f5f5;border:1px dashed #999;font-size:9pt;}
        .footer {margin-top:10px;text-align:center;font-size:8pt;border-top:1px solid #000;padding-top:5px;}
        @page {margin:1cm;}
    </style>
</head>
<body>
    <div class="header">
        <div class="school">{{ $exam->team ? $exam->team->name : 'School' }}</div>
        <h1>{{ $exam->title }}</h1>
    </div>

    <div class="info-box">
        <table class="info-table">
            <tr>
                <td class="label">Name:</td><td class="value"></td>
                <td class="label">Date:</td><td class="value"></td>
            </tr>
            <tr>
                <td class="label">ID:</td><td class="value"></td>
                <td class="label">Class:</td><td class="value"></td>
            </tr>
        </table>
    </div>

    @if($exam->description)
    <div class="info-box">
        <strong>INSTRUCTIONS:</strong>
        <div>{!! $exam->description !!}</div>
        <div><strong>Total: {{ $exam->total_points }} points</strong></div>
    </div>
    @endif

    <?php
        $questionsByType = $exam->questions->groupBy('type');
        $questionCounter = 1;
    ?>

    @if($questionsByType->has('multiple_choice'))
    <div class="section">MULTIPLE CHOICE</div>
    @foreach($questionsByType['multiple_choice'] as $question)
    <div class="q">
        <div class="q-header">
            <span>{{ $questionCounter }}.</span>
            <span class="q-points">({{ $question->points }}pts)</span>
        </div>
        <div>{!! $question->content !!}</div>

        <div class="options">
            @php
                $choices = null;
                // Try to get choices from the question object
                if (is_array($question->choices)) {
                    $choices = $question->choices;
                } elseif (is_string($question->choices)) {
                    $choices = json_decode($question->choices, true);
                }
            @endphp

            @if(is_array($choices))
                @foreach($choices as $letter => $text)
                <div class="option">
                    <div class="checkbox"></div>
                    <div style="margin-left:5px;">{{ $letter }}. {{ $text }}</div>
                </div>
                @endforeach
            @endif
        </div>

        @if($includeAnswerKey && !empty($question->correct_answer))
        <div class="answer">
            <strong>Answer:</strong>
            @php
                $answer = '';
                if (is_array($question->correct_answer)) {
                    $answer = $question->correct_answer[0];
                } elseif (is_string($question->correct_answer)) {
                    $answer = $question->correct_answer;
                }
            @endphp
            {{ $answer }}

            @if($question->explanation)
            <div><strong>Explanation:</strong> {{ $question->explanation }}</div>
            @endif
        </div>
        @endif
    </div>
    <?php $questionCounter++; ?>
    @endforeach
    @endif

    @if($questionsByType->has('true_false'))
    <div class="section">TRUE OR FALSE</div>
    @foreach($questionsByType['true_false'] as $question)
    <div class="q">
        <div class="q-header">
            <span>{{ $questionCounter }}.</span>
            <span class="q-points">({{ $question->points }}pts)</span>
        </div>
        <div>{!! $question->content !!}</div>

        <div class="options">
            <span style="margin-right:15px;"><span class="checkbox"></span> True</span>
            <span><span class="checkbox"></span> False</span>
        </div>

        @if($includeAnswerKey && !empty($question->correct_answer))
        <div class="answer">
            <strong>Answer:</strong>
            @php
                $answer = '';
                if (is_array($question->correct_answer)) {
                    $answer = $question->correct_answer[0];
                } elseif (is_string($question->correct_answer)) {
                    $answer = $question->correct_answer;
                }
            @endphp
            {{ $answer }}

            @if($question->explanation)
            <div><strong>Explanation:</strong> {{ $question->explanation }}</div>
            @endif
        </div>
        @endif
    </div>
    <?php $questionCounter++; ?>
    @endforeach
    @endif

    @if($questionsByType->has('short_answer'))
    <div class="section">SHORT ANSWER</div>
    @foreach($questionsByType['short_answer'] as $question)
    <div class="q">
        <div class="q-header">
            <span>{{ $questionCounter }}.</span>
            <span class="q-points">({{ $question->points }}pts)</span>
        </div>
        <div>{!! $question->content !!}</div>

        <div class="answer-space"></div>

        @if($includeAnswerKey && !empty($question->correct_answer))
        <div class="answer">
            <strong>Answer:</strong>
            @php
                $answer = '';
                if (is_array($question->correct_answer)) {
                    $answer = $question->correct_answer[0];
                } elseif (is_string($question->correct_answer)) {
                    $answer = $question->correct_answer;
                }
            @endphp
            {{ $answer }}

            @if($question->explanation)
            <div><strong>Explanation:</strong> {{ $question->explanation }}</div>
            @endif
        </div>
        @endif
    </div>
    <?php $questionCounter++; ?>
    @endforeach
    @endif

    @if($questionsByType->has('essay'))
    <div class="section">ESSAY</div>
    @foreach($questionsByType['essay'] as $question)
    <div class="q">
        <div class="q-header">
            <span>{{ $questionCounter }}.</span>
            <span class="q-points">({{ $question->points }}pts)</span>
        </div>
        <div>{!! $question->content !!}</div>

        @if($question->word_limit)
        <div style="text-align:right;font-size:8pt;font-style:italic;">Word limit: {{ $question->word_limit }}</div>
        @endif

        <div class="essay-space"></div>

        @if($includeAnswerKey && $question->rubric)
        <div class="answer">
            <strong>Rubric:</strong> {{ $question->rubric }}
        </div>
        @endif
    </div>
    <?php $questionCounter++; ?>
    @endforeach
    @endif

    @if($questionsByType->has('matching'))
    <div class="section">MATCHING</div>
    @foreach($questionsByType['matching'] as $question)
    <div class="q">
        <div class="q-header">
            <span>{{ $questionCounter }}.</span>
            <span class="q-points">({{ $question->points }}pts)</span>
        </div>
        <div>{!! $question->content !!}</div>

        @php
            $matchingPairs = null;

            // Try to get matching pairs from the question object
            if (is_array($question->matching_pairs)) {
                $matchingPairs = $question->matching_pairs;
            } elseif (is_string($question->matching_pairs)) {
                $matchingPairs = json_decode($question->matching_pairs, true);
            }

            $leftItems = [];
            $rightItems = [];

            // Extract data if we have a valid array
            if (is_array($matchingPairs)) {
                $leftItems = array_keys($matchingPairs);
                $rightItems = array_values($matchingPairs);
                shuffle($rightItems);
            }
        @endphp

        @if(!empty($leftItems) && !empty($rightItems))
        <div class="match-container">
            <div class="match-col">
                @foreach($leftItems as $index => $leftItem)
                <div class="match-item">{{ $index + 1 }}. {{ $leftItem }}</div>
                @endforeach
            </div>
            <div class="match-col">
                @foreach($rightItems as $index => $rightItem)
                <div class="match-item">{{ chr(65 + $index) }}. {{ $rightItem }}</div>
                @endforeach
            </div>
        </div>

        @if($includeAnswerKey && is_array($matchingPairs))
        <div class="answer">
            <strong>Matches:</strong><br>
            @foreach($matchingPairs as $left => $right)
            {{ array_search($left, $leftItems) + 1 }}. {{ $left }} → {{ $right }}<br>
            @endforeach
        </div>
        @endif
        @endif
    </div>
    <?php $questionCounter++; ?>
    @endforeach
    @endif

    @if($questionsByType->has('fill_in_blank'))
    <div class="section">FILL IN THE BLANK</div>
    @foreach($questionsByType['fill_in_blank'] as $question)
    <div class="q">
        <div class="q-header">
            <span>{{ $questionCounter }}.</span>
            <span class="q-points">({{ $question->points }}pts)</span>
        </div>

        <div>
            @php
                $content = $question->content;
                $content = preg_replace('/\[blank\]/', '<span class="blank"></span>', $content);
            @endphp
            {!! $content !!}
        </div>

        @php
            $answers = null;
            if (property_exists($question, 'answers')) {
                if (is_array($question->answers)) {
                    $answers = $question->answers;
                } elseif (is_string($question->answers)) {
                    $answers = json_decode($question->answers, true);
                }
            }
        @endphp

        @if($includeAnswerKey && is_array($answers))
        <div class="answer">
            <strong>Answers:</strong><br>
            @foreach($answers as $index => $answer)
                Blank {{ $index + 1 }}: {{ $answer }}<br>
            @endforeach
        </div>
        @endif
    </div>
    <?php $questionCounter++; ?>
    @endforeach
    @endif

    <div class="footer">
        End of Exam | {{ now()->format('m/d/Y') }} | {{ $exam->total_points }} points total
    </div>
</body>
</html>
