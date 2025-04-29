@php
use Illuminate\Support\Str;
use App\Models\Activity;
use App\Models\Team;

// Determine grading system specifics for easier conditional rendering
$isShs = $gradingSystemType === Team::GRADING_SYSTEM_SHS;
$isCollege = $gradingSystemType === Team::GRADING_SYSTEM_COLLEGE;
$isCollegeTerm = $team?->usesCollegeTermGrading() ?? false;
$isCollegeGwa = $team?->usesCollegeGwaGrading() ?? false;
$numericScale = $team?->getCollegeNumericScale();

// Helper for badge classes (reduces repetition)
$getBadgeClass = function(string $type, string $value) {
    if ($type === 'shs') {
        return match($value) {
            Activity::COMPONENT_WRITTEN_WORK => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            Activity::COMPONENT_PERFORMANCE_TASK => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            Activity::COMPONENT_QUARTERLY_ASSESSMENT => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
            default => '',
        };
    } elseif ($type === 'term') {
        return match($value) {
            Activity::TERM_PRELIM => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
            Activity::TERM_MIDTERM => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
            Activity::TERM_FINAL => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            default => '',
        };
    }
    return '';
};

@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Gradesheet - {{ $team->name }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Grading System: <span class="font-semibold">{{ $team->grading_system_description }}</span>
                    </p>
                </div>
                {{-- Legend based on Grading System --}}
                @if ($isShs)
                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex items-center gap-1.5">
                            <span class="{{ $getBadgeClass('shs', Activity::COMPONENT_WRITTEN_WORK) }} legend-badge">WW</span>
                            <span>Written Work</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                             <span class="{{ $getBadgeClass('shs', Activity::COMPONENT_PERFORMANCE_TASK) }} legend-badge">PT</span>
                            <span>Performance Task</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="{{ $getBadgeClass('shs', Activity::COMPONENT_QUARTERLY_ASSESSMENT) }} legend-badge">QA</span>
                            <span>Quarterly Assessment</span>
                        </div>
                    </div>
                @elseif ($isCollegeTerm)
                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                         <div class="flex items-center gap-1.5">
                             <span class="{{ $getBadgeClass('term', Activity::TERM_PRELIM) }} legend-badge">PRE</span>
                             <span>Prelim</span>
                         </div>
                         <div class="flex items-center gap-1.5">
                            <span class="{{ $getBadgeClass('term', Activity::TERM_MIDTERM) }} legend-badge">MID</span>
                            <span>Midterm</span>
                         </div>
                         <div class="flex items-center gap-1.5">
                             <span class="{{ $getBadgeClass('term', Activity::TERM_FINAL) }} legend-badge">FIN</span>
                             <span>Final</span>
                         </div>
                     </div>
                 @elseif ($isCollegeGwa)
                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-o-scale class="h-5 w-5 text-gray-400" />
                            <span>GWA calculation based on Credit Units</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Grading Configuration Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            {{ $this->form }} {{-- Form is defined in the PHP class --}}
        </div>

        {{-- Grades Table Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Student Grades</h3>
                    <div class="flex items-center gap-3">
                        {{-- Save Button --}}
                        <button
                            type="button"
                            wire:click="saveAllScores"
                            wire:loading.attr="disabled"
                            wire:target="saveAllScores"
                            class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:opacity-50">
                            <x-heroicon-m-arrow-path wire:loading wire:target="saveAllScores" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"/>
                            <x-heroicon-o-check wire:loading.remove wire:target="saveAllScores" class="w-4 h-4 mr-2"/>
                            Save All Scores
                        </button>

                        {{-- Grade Color Legend --}}
                        <div class="hidden sm:flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 border-l pl-3 ml-3 border-gray-200 dark:border-gray-600">
                            @php
                                $legendItems = [];
                                $numericScale = $team?->getCollegeNumericScale();

                                if ($isShs) {
                                    $legendItems = [
                                        ['label' => '≥90', 'color' => 'success'], ['label' => '≥85', 'color' => 'primary'],
                                        ['label' => '≥80', 'color' => 'info'], ['label' => '≥75', 'color' => 'warning'], ['label' => '<75', 'color' => 'danger'],
                                    ];
                                } elseif ($isCollege && $numericScale === 'percentage') {
                                    $legendItems = [
                                        ['label' => '≥90%', 'color' => 'success'], ['label' => '≥80%', 'color' => 'primary'],
                                        ['label' => '≥75%', 'color' => 'warning'], ['label' => '<75%', 'color' => 'danger'],
                                    ];
                                } elseif ($isCollege && $numericScale === '5_point') {
                                     $legendItems = [
                                         ['label' => '≤1.50', 'color' => 'success'], ['label' => '≤2.00', 'color' => 'primary'],
                                         ['label' => '≤2.50', 'color' => 'info'], ['label' => '≤3.00', 'color' => 'warning'], ['label' => '>3.00', 'color' => 'danger'],
                                     ];
                                } elseif ($isCollege && $numericScale === '4_point') {
                                     $legendItems = [
                                         ['label' => '≥3.7', 'color' => 'success'], ['label' => '≥3.0', 'color' => 'primary'],
                                         ['label' => '≥2.0', 'color' => 'info'], ['label' => '≥1.0', 'color' => 'warning'], ['label' => '<1.0', 'color' => 'danger'],
                                     ];
                                }
                            @endphp

                            @foreach($legendItems as $item)
                                <span class="inline-flex items-center gap-1">
                                   <span @class([
                                       'w-2 h-2 rounded-full',
                                       'bg-success-500' => $item['color'] === 'success',
                                       'bg-primary-500' => $item['color'] === 'primary',
                                       'bg-info-500' => $item['color'] === 'info',
                                       'bg-warning-500' => $item['color'] === 'warning',
                                       'bg-danger-500' => $item['color'] === 'danger',
                                   ])></span>
                                   {{ $item['label'] }}
                               </span>
                           @endforeach
                        </div>
                    </div>
                </div>
                {{-- Display validation errors --}}
                @if ($errors->has('activityScores.*'))
                    <div class="mt-3 text-sm text-danger-600 dark:text-danger-400">
                        Please correct the score input errors highlighted below. Scores must be between 0 and the activity's total points.
                    </div>
                @endif
            </div>

            {{-- Grades Table --}}
            <div class="overflow-x-auto">
                @if ($students->isEmpty())
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">No active students found.</div>
                @elseif ($activities->isEmpty())
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">No published activities found.</div>
                @else
                    <table class="fi-ta-table w-full min-w-max divide-y divide-gray-200 dark:divide-white/5">
                        {{-- TABLE HEADER --}}
                        <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                            <tr class="fi-ta-header-row">
                                {{-- Student Name Header --}}
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start whitespace-nowrap sticky left-0 bg-gray-50 dark:bg-gray-800 z-10">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Student Name</span>
                                </th>

                                {{-- Activity Headers --}}
                                @foreach ($activities as $activity)
                                    @php
                                        $headerClass = 'bg-gray-50 dark:bg-white/5';
                                        $icon = null;
                                        $iconClass = '';
                                        $prefix = '';
                                        $tooltip = $activity->title;

                                        if ($isShs && $activity->component_type) {
                                            $headerClass = $this->getShsComponentHeaderClass($activity->component_type);
                                            $prefix = $activity->component_type_code;
                                            $icon = match($activity->component_type) {
                                                Activity::COMPONENT_WRITTEN_WORK => 'heroicon-o-pencil-square',
                                                Activity::COMPONENT_PERFORMANCE_TASK => 'heroicon-o-user-group',
                                                Activity::COMPONENT_QUARTERLY_ASSESSMENT => 'heroicon-o-chart-bar-square',
                                                default => null,
                                            };
                                            $iconClass = match($activity->component_type) {
                                                Activity::COMPONENT_WRITTEN_WORK => 'text-blue-600 dark:text-blue-400',
                                                Activity::COMPONENT_PERFORMANCE_TASK => 'text-red-600 dark:text-red-400',
                                                Activity::COMPONENT_QUARTERLY_ASSESSMENT => 'text-yellow-600 dark:text-yellow-400',
                                                default => '',
                                            };
                                            $tooltip = $activity->getComponentTypeDescriptionAttribute() . ": " . $activity->title;
                                        } elseif ($isCollegeTerm && $activity->term) {
                                            $headerClass = $this->getTermHeaderClass($activity->term);
                                            $prefix = $activity->getTermCode();
                                            $icon = match($activity->term) {
                                                Activity::TERM_PRELIM => 'heroicon-o-calendar-days',
                                                Activity::TERM_MIDTERM => 'heroicon-o-calendar',
                                                Activity::TERM_FINAL => 'heroicon-o-flag',
                                                default => null,
                                            };
                                            $iconClass = match($activity->term) {
                                                Activity::TERM_PRELIM => 'text-teal-600 dark:text-teal-400',
                                                Activity::TERM_MIDTERM => 'text-purple-600 dark:text-purple-400',
                                                Activity::TERM_FINAL => 'text-orange-600 dark:text-orange-400',
                                                default => '',
                                            };
                                            $tooltip = $activity->getTermDescriptionAttribute() . ": " . $activity->title;
                                            // Add WW/PT indicator for college term using string comparison
                                            if ($activity->category === 'written') $prefix .= ' (WW)';
                                            if ($activity->category === 'performance') $prefix .= ' (PT)';
                                        }
                                    @endphp
                                    <th @class([
                                        'fi-ta-header-cell px-3 py-3.5 text-center whitespace-nowrap border-x border-gray-200 dark:border-white/10',
                                        $headerClass
                                    ]) title="{{ $tooltip }}">
                                        <div class="flex flex-col items-center space-y-1">
                                            <div class="flex items-center justify-center gap-1 text-xs font-bold {{ $iconClass }}">
                                                @if($icon) <x-dynamic-component :component="$icon" class="w-3.5 h-3.5" /> @endif
                                                @if($prefix) <span>[{{ $prefix }}]</span> @endif
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ Str::limit($activity->title, 25) }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                ({{ $activity->total_points ?? 'N/A' }} pts
                                                @if ($isCollegeGwa && $activity->credit_units > 0)
                                                    / {{ $activity->credit_units }} units
                                                @endif
                                                )
                                            </span>
                                        </div>
                                    </th>
                                @endforeach

                                {{-- Calculated Term Grade Headers (College Term Only) --}}
                                @if ($isCollegeTerm)
                                    <th class="fi-ta-header-cell px-3 py-3.5 text-center whitespace-nowrap bg-teal-100 dark:bg-teal-900 border-x border-gray-200 dark:border-white/10">
                                        <span class="text-sm font-semibold text-teal-800 dark:text-teal-200">Prelim Grade</span>
                                    </th>
                                    <th class="fi-ta-header-cell px-3 py-3.5 text-center whitespace-nowrap bg-purple-100 dark:bg-purple-900 border-x border-gray-200 dark:border-white/10">
                                        <span class="text-sm font-semibold text-purple-800 dark:text-purple-200">Midterm Grade</span>
                                    </th>
                                    <th class="fi-ta-header-cell px-3 py-3.5 text-center whitespace-nowrap bg-orange-100 dark:bg-orange-900 border-x border-gray-200 dark:border-white/10">
                                        <span class="text-sm font-semibold text-orange-800 dark:text-orange-200">Final Term Grade</span>
                                    </th>
                                @endif

                                {{-- Overall Grade Header --}}
                                @if ($showFinalGrades)
                                    <th class="fi-ta-header-cell px-3 py-3.5 sm:last-of-type:pe-6 text-center whitespace-nowrap bg-gray-100 dark:bg-gray-700 border-l border-gray-200 dark:border-white/10 sticky right-0 z-10">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            @if ($isShs) Overall (Transmuted) @endif
                                            @if ($isCollegeTerm) Final Grade (Avg) @endif
                                            @if ($isCollegeGwa) GWA @endif
                                            @if (!$isShs && !$isCollegeTerm && !$isCollegeGwa) Overall Grade @endif
                                        </span>
                                        <span class="block text-xs text-gray-400 dark:text-gray-500">(Click to View)</span>
                                    </th>
                                @endif
                            </tr>
                        </thead>

                        {{-- TABLE BODY --}}
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($students as $student)
                                <tr class="fi-ta-row h-16 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-75">
                                    {{-- Student Name Cell --}}
                                    <td class="fi-ta-cell px-3 py-2 sm:first-of-type:ps-6 sm:last-of-type:pe-6 sticky left-0 bg-white dark:bg-gray-800 z-0">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $student->name }}</span>
                                    </td>

                                    {{-- Score Input Cells --}}
                                    @foreach ($activities as $activity)
                                        @php
                                            $wireModelKey = "activityScores.{$student->id}.{$activity->id}";
                                            $cellClass = 'bg-white dark:bg-gray-800'; // Default
                                            if ($isShs && $activity->component_type) {
                                                $cellClass = $this->getShsComponentHeaderClass($activity->component_type);
                                            } elseif ($isCollegeTerm && $activity->term) {
                                                $cellClass = $this->getTermHeaderClass($activity->term);
                                            }
                                        @endphp
                                        <td @class([
                                            'fi-ta-cell px-3 py-2 text-center border-x border-gray-200 dark:border-white/5',
                                            $cellClass . ' opacity-80'
                                        ])>
                                            <input
                                                type="number"
                                                wire:model.defer="{{ $wireModelKey }}"
                                                min="0"
                                                @if($activity->total_points !== null) max="{{ $activity->total_points }}" @endif
                                                step="0.01"
                                                placeholder="-"
                                                aria-label="Score for {{ $student->name }} on {{ $activity->title }}"
                                                @class([
                                                    'fi-input block w-20 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-70 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:border-gray-600 text-center text-sm py-1.5 px-1',
                                                    '!border-danger-600 dark:!border-danger-500' => $errors->has($wireModelKey)
                                                ])
                                            />
                                            @error($wireModelKey)
                                                <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    @endforeach

                                    {{-- Calculated Term Grade Cells (College Term Only) --}}
                                    @if ($isCollegeTerm)
                                        <td class="fi-ta-cell px-3 py-2 text-center font-medium bg-teal-50 dark:bg-teal-900/50 border-x border-gray-200 dark:border-white/10">
                                            {!! $this->getFormattedTermGrade($student->id, Activity::TERM_PRELIM) !!}
                                        </td>
                                        <td class="fi-ta-cell px-3 py-2 text-center font-medium bg-purple-50 dark:bg-purple-900/50 border-x border-gray-200 dark:border-white/10">
                                            {!! $this->getFormattedTermGrade($student->id, Activity::TERM_MIDTERM) !!}
                                        </td>
                                        <td class="fi-ta-cell px-3 py-2 text-center font-medium bg-orange-50 dark:bg-orange-900/50 border-x border-gray-200 dark:border-white/10">
                                            {!! $this->getFormattedTermGrade($student->id, Activity::TERM_FINAL) !!}
                                        </td>
                                    @endif

                                    {{-- Overall Grade Cell --}}
                                    @if ($showFinalGrades)
                                        <td class="fi-ta-cell px-3 py-2 sm:last-of-type:pe-6 text-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors bg-gray-100 dark:bg-gray-700 border-l border-gray-200 dark:border-white/10 sticky right-0 z-0"
                                            wire:click="openFinalGradeModal('{{ $student->id }}')"
                                            title="Click to view grade breakdown for {{ $student->name }}">
                                             {!! $this->getFormattedOverallGrade($student->id) !!}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Grading Information Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Grading System Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-600 dark:text-gray-300">
                @if ($isShs)
                    {{-- SHS Info --}}
                    <div class="space-y-3">
                         <p><strong>System:</strong> K-12 Senior High School</p>
                         <p><strong>Components & Weights:</strong></p>
                         <ul class="list-disc list-inside ml-4">
                             <li>Written Work (WW): <strong>{{ $shsWrittenWorkWeight ?? 'N/A' }}%</strong></li>
                             <li>Performance Task (PT): <strong>{{ $shsPerformanceTaskWeight ?? 'N/A' }}%</strong></li>
                             <li>Quarterly Assessment (QA): <strong>{{ $shsQuarterlyAssessmentWeight ?? 'N/A' }}%</strong></li>
                         </ul>
                         <p><strong>Calculation:</strong></p>
                         <ol class="list-decimal list-inside ml-4 space-y-1">
                             <li>Sum raw scores per component.</li>
                             <li>Calculate Percentage Score (PS) per component: (Total Raw / Total Possible) * 100.</li>
                             <li>Calculate Weighted Score (WS) per component: PS * Weight.</li>
                             <li>Sum all Weighted Scores = Initial Grade.</li>
                             <li>Transmute Initial Grade using DepEd table for reporting.</li>
                         </ol>
                         <p><strong>Passing Grade (Transmuted):</strong> 75 (Fairly Satisfactory)</p>
                     </div>
                     <div class="space-y-3">
                         <p><strong>Descriptors:</strong></p>
                         <ul class="list-disc list-inside ml-4">
                             <li>90-100: Outstanding</li>
                             <li>85-89: Very Satisfactory</li>
                             <li>80-84: Satisfactory</li>
                             <li>75-79: Fairly Satisfactory</li>
                             <li>Below 75: Did Not Meet Expectations</li>
                         </ul>
                         <p class="mt-4 text-xs text-gray-500">* Overall grade shown is the transmuted grade.</p>
                     </div>
                @elseif ($isCollegeTerm)
                    {{-- College Term Info --}}
                    <div class="space-y-3">
                         <p><strong>System:</strong> College/University (Term-Based)</p>
                         <p><strong>Scale:</strong> {{ $collegeGradingScale ? str_replace('_', ' ', Str::title($collegeGradingScale)) : 'Not Set' }}</p>
                         <p><strong>Term Component Weights:</strong></p>
                         <ul class="list-disc list-inside ml-4">
                             <li>Written Work (WW): <strong>{{ $this->collegeTermWwWeight ?? 'N/A' }}%</strong></li>
                             <li>Performance Task (PT): <strong>{{ $this->collegeTermPtWeight ?? 'N/A' }}%</strong></li>
                         </ul>
                         <p><strong>Overall Term Weights:</strong></p>
                         <ul class="list-disc list-inside ml-4">
                             <li>Prelim: <strong>{{ $collegePrelimWeight ?? 'N/A' }}%</strong></li>
                             <li>Midterm: <strong>{{ $collegeMidtermWeight ?? 'N/A' }}%</strong></li>
                             <li>Final: <strong>{{ $collegeFinalWeight ?? 'N/A' }}%</strong></li>
                         </ul>
                     </div>
                     <div class="space-y-3">
                        <p><strong>Calculation:</strong></p>
                         <ol class="list-decimal list-inside ml-4 space-y-1">
                             <li><strong>Within each term (Prelim, Midterm, Final):</strong></li>
                                <li class="ml-4">Calculate average percentage for Written Work (WW) activities.</li>
                                <li class="ml-4">Calculate average percentage for Performance Task (PT) activities.</li>
                                <li class="ml-4">Apply WW/PT weights: (WW Avg % * WW Weight) + (PT Avg % * PT Weight) = Term Percentage.</li>
                                <li class="ml-4">Convert Term Percentage to the selected grading scale (e.g., 1.75, 85.00) = Term Grade.</li>
                             <li><strong>Final Grade:</strong></li>
                                <li class="ml-4">Apply overall term weights: (Prelim Grade * Prelim W.) + (Midterm Grade * Midterm W.) + (Final Term Grade * Final W.) = Final Grade.</li>
                                <li class="ml-4">Weights are adjusted proportionally if a term has no grade.</li>
                         </ol>
                        <p><strong>Passing Grade:</strong> Varies by institution.</p>
                        <p class="mt-4 text-xs text-gray-500">* Table shows calculated term grades and the final weighted average grade.</p>
                     </div>
                @elseif ($isCollegeGwa)
                     {{-- College GWA Info --}}
                     <div class="space-y-3">
                         <p><strong>System:</strong> College/University (GWA-Based)</p>
                         <p><strong>Scale:</strong> {{ $collegeGradingScale ? str_replace('_', ' ', Str::title($collegeGradingScale)) : 'Not Set' }}</p>
                         <p><strong>Calculation (GWA):</strong></p>
                         <ol class="list-decimal list-inside ml-4 space-y-1">
                             <li>For each activity with a score and positive credit units:</li>
                                 <li class="ml-4">Calculate percentage: (Score / Total Points) * 100.</li>
                                 <li class="ml-4">Convert percentage to the selected scale value (e.g., 1.75, 85.00).</li>
                                 <li class="ml-4">Multiply scale grade by activity's credit units = Weighted Part.</li>
                             <li>Sum all Weighted Parts.</li>
                             <li>Sum all credit units for included activities.</li>
                             <li>GWA = Total Weighted Parts / Total Credit Units.</li>
                         </ol>
                         <p class="mt-2 text-xs text-gray-500">* Specific percentage-to-scale conversions vary.</p>
                     </div>
                     <div class="space-y-3">
                         <p><strong>Credit Units:</strong> Required for activities to be included in GWA.</p>
                         <p><strong>Passing Grade:</strong> Varies by institution.</p>
                         <p class="mt-4 text-xs text-gray-500">* Table shows calculated GWA.</p>
                     </div>
                @else
                    <div class="text-center text-gray-500 col-span-2">Please select and configure a grading system above.</div>
                @endif
            </div>
        </div>

    </div> {{-- End main space-y-6 --}}

    {{-- MODAL FOR FINAL GRADE --}}
    <div wire:ignore.self>
        <x-filament::modal id="final-grade-modal" width="4xl"> {{-- Made wider --}}
            <x-slot name="heading">Grade Breakdown (<span id="modal-student-name">Student</span>)</x-slot>
            <x-slot name="description">Detailed calculation for the selected grading system.</x-slot>

            <div id="final-grade-content" class="space-y-6 text-sm">
                {{-- Loading State --}}
                <div id="modal-loading-state" class="space-y-4 animate-pulse">
                   <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
                   <div class="grid grid-cols-2 gap-4">
                       <div class="h-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                       <div class="h-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                   </div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                   <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mt-6"></div>
                   <div class="space-y-2">
                       <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                       <div class="h-8 bg-gray-100 dark:bg-gray-700/50 rounded"></div>
                       <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                   </div>
               </div>
                {{-- Content loaded by JS --}}
            </div>

            <x-slot name="footer">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'final-grade-modal' })">
                    Close
                </x-filament::button>
           </x-slot>
        </x-filament::modal>
    </div>

    @push('styles')
        <style>
            .legend-badge {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.75rem; /* w-7 */
                height: 1.5rem; /* h-6 */
                border-radius: 0.375rem; /* rounded-md */
                font-size: 0.75rem; /* text-xs */
                font-weight: 700; /* font-bold */
                line-height: 1rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                let currentStudentId = null;

                Livewire.on('open-modal', (event) => {
                    if (event.id === 'final-grade-modal' && event.studentId) {
                        currentStudentId = event.studentId;
                        loadFinalGradeContent(currentStudentId);
                    }
                });

                function loadFinalGradeContent(studentId) {
                    const contentContainer = document.getElementById('final-grade-content');
                    const loadingIndicator = document.getElementById('modal-loading-state');
                    const studentNameSpan = document.getElementById('modal-student-name');
                    loadingIndicator.style.display = 'block';
                    contentContainer.querySelectorAll(':not(#modal-loading-state)').forEach(el => el.remove());
                    studentNameSpan.textContent = 'Loading...';

                    @this.getStudentGradeBreakdown(studentId)
                        .then(result => {
                            loadingIndicator.style.display = 'none';
                            if (result.error) {
                                contentContainer.innerHTML = `<p class="text-danger-600 dark:text-danger-400">${result.error}</p>`;
                                studentNameSpan.textContent = 'Error';
                                return;
                            }
                            studentNameSpan.textContent = result.student?.name || 'Student';

                            if (result.breakdown?.type === 'shs') {
                                renderShsModalContent(contentContainer, result);
                            } else if (result.breakdown?.type === 'college_term') {
                                renderCollegeTermModalContent(contentContainer, result);
                            } else if (result.breakdown?.type === 'college_gwa') {
                                renderCollegeGwaModalContent(contentContainer, result);
                            } else {
                                contentContainer.innerHTML = '<p>Grading system not configured or data unavailable.</p>';
                            }
                        })
                        .catch(error => {
                            console.error('Error loading grade breakdown:', error);
                            loadingIndicator.style.display = 'none';
                            contentContainer.innerHTML = '<p class="text-danger-600 dark:text-danger-400">Failed to load grade breakdown. Please try again.</p>';
                            studentNameSpan.textContent = 'Error';
                        });
                }

                function renderShsModalContent(container, data) {
                    const breakdown = data.breakdown;
                    const activities = data.activities;
                    const getShsBadgeClass = (val) => `{{ $getBadgeClass('shs', '${val}') }}`;

                    const renderComponent = (compData, weight, title, code, badgeClass) => {
                        return `
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4 border dark:border-gray-700">
                                <h4 class="text-base font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                    <span class="${badgeClass} legend-badge">${code}</span> ${title} (${weight}%)
                                </h4>
                                <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Total Raw Score:</span>
                                    <span class="text-right font-medium">${compData.formatted_raw}</span>
                                    <span class="text-gray-500 dark:text-gray-400">Percentage Score (PS):</span>
                                    <span class="text-right font-medium">${compData.formatted_ps}</span>
                                    <span class="text-gray-500 dark:text-gray-400">Weighted Score (WS):</span>
                                    <span class="text-right font-bold text-primary-600 dark:text-primary-400">${compData.formatted_ws}</span>
                                </div>
                            </div>`;
                    };

                    const getActivityRow = (act) => {
                        const score = act.score !== null ? act.score : '-';
                        const badgeClass = act.component_type ? getShsBadgeClass(act.component_type) : '';
                        return `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                    ${act.component_code ? `<span class="legend-badge ${badgeClass}">${act.component_code}</span>` : ''}
                                    ${act.title}
                                </td>
                                <td class="px-3 py-1.5 text-right font-medium">${score}</td>
                                <td class="px-3 py-1.5 text-right text-gray-500 dark:text-gray-400">${act.total_points ?? 'N/A'}</td>
                                <td class="px-3 py-1.5 text-right font-medium ${act.color_class}">${act.formatted_percentage}</td>
                            </tr>`;
                    };

                    const wwActivities = activities.filter(a => a.component_type === 'written_work');
                    const ptActivities = activities.filter(a => a.component_type === 'performance_task');
                    const qaActivities = activities.filter(a => a.component_type === 'quarterly_assessment');
                    const allCompActivities = [...wwActivities, ...ptActivities, ...qaActivities];

                    let html = `
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                            ${renderComponent(breakdown.components.ww, breakdown.weights.ww, 'Written Work', 'WW', getShsBadgeClass('written_work'))}
                            ${renderComponent(breakdown.components.pt, breakdown.weights.pt, 'Performance Task', 'PT', getShsBadgeClass('performance_task'))}
                            ${renderComponent(breakdown.components.qa, breakdown.weights.qa, 'Quarterly Assessment', 'QA', getShsBadgeClass('quarterly_assessment'))}
                        </div>
                        <div class="rounded-lg bg-gray-100 dark:bg-gray-700/50 p-4 border dark:border-gray-600">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white">Overall Grade Calculation</h4>
                            <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                <span class="text-gray-600 dark:text-gray-300">Initial Grade (Σ WS):</span>
                                <span class="text-right font-semibold text-lg text-primary-700 dark:text-primary-300">${breakdown.formatted_initial_grade}</span>
                                <span class="text-gray-600 dark:text-gray-300">Transmuted Grade:</span>
                                <span class="text-right font-bold text-2xl ${breakdown.color_class}">${breakdown.formatted_transmuted_grade}</span>
                                <span class="text-gray-600 dark:text-gray-300">Descriptor:</span>
                                <span class="text-right font-semibold text-lg ${breakdown.color_class}">${breakdown.descriptor}</span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mt-4">Activity Scores</h4>
                            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Activity (Component)</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Score</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">%</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900/50">
                                        ${allCompActivities.length ? allCompActivities.map(getActivityRow).join('') : '<tr><td colspan="4" class="text-center py-4 text-gray-500">No activities found.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                    container.innerHTML = html;
                }

                // *** UPDATED College Term Modal Function ***
                function renderCollegeTermModalContent(container, data) {
                    const breakdown = data.breakdown;
                    const activities = data.activities;
                    const formattedTermGrades = breakdown.formatted_term_grades || {};
                    const termGradeColors = breakdown.term_grade_colors || {};
                    const termDetails = breakdown.term_component_details || {};
                    const componentWeights = breakdown.component_weights || {};
                    const termWeights = breakdown.term_weights || {};
                    const getTermBadgeClass = (val) => `{{ $getBadgeClass('term', '${val}') }}`;

                    // Render card for each term including WW/PT breakdown
                    const renderTermCard = (termKey, termLabel, termWeight, termBadgeClass) => {
                        const grade = formattedTermGrades[termKey] || 'N/A';
                        const color = termGradeColors[termKey] || 'text-gray-400';
                        const details = termDetails[termKey] || {};
                        const wwWeight = componentWeights.ww || 0;
                        const ptWeight = componentWeights.pt || 0;

                        const wwAvgHtml = details.ww_formatted_avg ? `(${details.ww_formatted_avg})` : '(N/A)';
                        const ptAvgHtml = details.pt_formatted_avg ? `(${details.pt_formatted_avg})` : '(N/A)';
                        const wwWeightedHtml = details.ww_weighted_part !== null ? details.ww_weighted_part.toFixed(2) : 'N/A';
                        const ptWeightedHtml = details.pt_weighted_part !== null ? details.pt_weighted_part.toFixed(2) : 'N/A';

                        return `
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4 border dark:border-gray-700 space-y-3">
                                <h4 class="text-base font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                    <span class="${termBadgeClass} legend-badge">${termKey.substring(0,3).toUpperCase()}</span>
                                    ${termLabel} Grade (${termWeight}% of Final)
                                </h4>
                                {{-- Component Breakdown within Term --}}
                                <div class="text-xs space-y-1 border-t pt-2 dark:border-gray-600">
                                    <div class="flex justify-between"><span>WW Avg ${wwAvgHtml} x ${wwWeight}%:</span> <span class="font-medium">${wwWeightedHtml}</span></div>
                                    <div class="flex justify-between"><span>PT Avg ${ptAvgHtml} x ${ptWeight}%:</span> <span class="font-medium">${ptWeightedHtml}</span></div>
                                    <div class="flex justify-between font-semibold border-t pt-1 dark:border-gray-700"><span>= Term Grade:</span> <span class="text-lg ${color}">${grade}</span></div>
                                </div>
                            </div>`;
                    };

                     const getActivityRow = (act) => {
                        if (!act.term) return ''; // Only show term activities
                        const score = act.score !== null ? act.score : '-';
                        const termBadgeClass = act.term ? getTermBadgeClass(act.term) : '';
                        // Add WW/PT indicator
                        const categoryIndicator = act.category === 'written' ? '(WW)' : (act.category === 'performance' ? '(PT)' : '');
                        return `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                    ${act.term_code ? `<span class="legend-badge ${termBadgeClass}">${act.term_code}</span>` : ''}
                                    ${act.title} <span class="text-xs text-gray-400">${categoryIndicator}</span>
                                </td>
                                <td class="px-3 py-1.5 text-right font-medium">${score}</td>
                                <td class="px-3 py-1.5 text-right text-gray-500 dark:text-gray-400">${act.total_points ?? 'N/A'}</td>
                                <td class="px-3 py-1.5 text-right font-medium ${act.color_class}">${act.formatted_percentage}</td>
                            </tr>`;
                    };

                    const prelimActivities = activities.filter(a => a.term === 'prelim');
                    const midtermActivities = activities.filter(a => a.term === 'midterm');
                    const finalActivities = activities.filter(a => a.term === 'final');
                    const allTermActivities = [...prelimActivities, ...midtermActivities, ...finalActivities];

                    const calculateFinalWeightedPart = (termKey) => {
                        const grade = breakdown.term_grades ? breakdown.term_grades[termKey] : null;
                        const weight = termWeights[termKey] || 0;
                        return grade !== null ? (grade * (weight / 100)).toFixed(2) : 'N/A';
                    };

                    let html = `
                        <p class="text-xs text-gray-500 dark:text-gray-400">Scale: ${breakdown.scale_description}</p>
                        {{-- Term Grade Summary with Component Breakdown --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                            ${renderTermCard('prelim', 'Prelim', termWeights.prelim, getTermBadgeClass('prelim'))}
                            ${renderTermCard('midterm', 'Midterm', termWeights.midterm, getTermBadgeClass('midterm'))}
                            ${renderTermCard('final', 'Final Term', termWeights.final, getTermBadgeClass('final'))}
                        </div>

                        {{-- Final Grade Calculation Box --}}
                        <div class="rounded-lg bg-gray-100 dark:bg-gray-700/50 p-4 border dark:border-gray-600">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white">Final Grade Calculation</h4>
                            <div class="mt-3 text-sm space-y-1">
                                <div class="flex justify-between"><span>(Prelim Grade × ${termWeights.prelim}%)</span> <span>${calculateFinalWeightedPart('prelim')}</span></div>
                                <div class="flex justify-between"><span>+ (Midterm Grade × ${termWeights.midterm}%)</span> <span>${calculateFinalWeightedPart('midterm')}</span></div>
                                <div class="flex justify-between border-b pb-1 dark:border-gray-600"><span>+ (Final Term Grade × ${termWeights.final}%)</span> <span>${calculateFinalWeightedPart('final')}</span></div>
                                <div class="flex justify-between font-bold pt-1">
                                    <span>= Final Grade</span>
                                    <span class="text-xl ${breakdown.final_grade_color}">${breakdown.formatted_final_grade}</span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 pt-2">* Term weights adjusted proportionally if a term has no calculated grade.</p>
                            </div>
                        </div>

                        {{-- Activity Breakdown Table --}}
                        <div class="space-y-4">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mt-4">Activity Scores by Term</h4>
                            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 max-h-80 overflow-y-auto"> {{-- Reduced max height --}}
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Activity (Term & Type)</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Score</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">%</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900/50">
                                         ${allTermActivities.length ? allTermActivities.map(getActivityRow).join('') : '<tr><td colspan="4" class="text-center py-4 text-gray-500">No activities found for any term.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                    container.innerHTML = html;
                }

                function renderCollegeGwaModalContent(container, data) {
                    const breakdown = data.breakdown;
                    const activities = data.activities;
                    const activityGrades = breakdown.activity_grades || {};

                    const getActivityRow = (act) => {
                        const score = act.score !== null ? act.score : '-';
                        const units = act.credit_units > 0 ? act.credit_units.toFixed(2) : '-';
                        const actGradeInfo = activityGrades[act.id];

                        if (!actGradeInfo) return ''; // Only show included activities

                        return `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200">${act.title}</td>
                                <td class="px-3 py-1.5 text-right font-medium">${score} / ${act.total_points ?? '?'}</td>
                                <td class="px-3 py-1.5 text-right font-medium ${actGradeInfo.color_class}">${actGradeInfo.formatted_scale_grade}</td>
                                <td class="px-3 py-1.5 text-right text-gray-500 dark:text-gray-400">${units}</td>
                                <td class="px-3 py-1.5 text-right font-semibold text-primary-600 dark:text-primary-400">${actGradeInfo.formatted_weighted_part}</td>
                            </tr>`;
                    };

                    let html = `
                        <div class="rounded-lg bg-gray-100 dark:bg-gray-700/50 p-4 border dark:border-gray-600 mb-6">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white">Overall GWA Calculation</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Scale: ${breakdown.scale_description}</p>
                            <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                <span class="text-gray-600 dark:text-gray-300">Sum of (Scale Grade x Units):</span>
                                <span class="text-right font-semibold text-lg text-primary-700 dark:text-primary-300">${breakdown.formatted_weighted_grade_sum}</span>
                                <span class="text-gray-600 dark:text-gray-300">Total Credit Units Included:</span>
                                <span class="text-right font-semibold text-lg text-primary-700 dark:text-primary-300">${breakdown.formatted_total_units}</span>
                                <span class="text-gray-600 dark:text-gray-300">Calculated GWA:</span>
                                <span class="text-right font-bold text-2xl ${breakdown.gwa_color}">${breakdown.formatted_gwa}</span>
                                <span class="text-gray-600 dark:text-gray-300 col-span-2 text-xs mt-1">GWA = Sum of (Scale Grade x Units) / Total Credit Units</span>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white mt-4">Activities Included in GWA</h4>
                             <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 max-h-80 overflow-y-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Activity</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Score / Total</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Scale Grade</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Units</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Weighted Part</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900/50">
                                        ${Object.keys(activityGrades).length ? activities.map(getActivityRow).join('') : '<tr><td colspan="5" class="text-center py-4 text-gray-500">No activities with scores and positive units found.</td></tr>'}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                    container.innerHTML = html;
                }

            }); // End Livewire::init
        </script>
    @endpush

</x-filament-panels::page>
