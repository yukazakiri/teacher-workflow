<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Gradesheet</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Manage and view student grades for all activities in this team.
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">W</span>
                        <span class="text-sm text-gray-600 dark:text-gray-300">Written</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">P</span>
                        <span class="text-sm text-gray-600 dark:text-gray-300">Performance</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grading Configuration Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            {{ $this->form }}
        </div>

        {{-- Grades Table Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Student Grades</h3>
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="saveAllScores" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Save All Scores
                        </button>
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-success-500"></span>
                                ≥90%
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                                ≥80%
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-warning-500"></span>
                                ≥70%
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                                <70%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full fi-ta-table divide-y divide-gray-200 dark:divide-white/5">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr class="fi-ta-header-row">
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Student Name</span>
                            </th>
                            @php
                                $activities = \App\Models\Activity::where('team_id', $this->teamId)
                                    ->orderBy('category')
                                    ->orderBy('created_at')
                                    ->get();
                            @endphp
                            @foreach ($activities as $activity)
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-center">
                                    <div class="flex flex-col items-center">
                                        @if ($activity->isWrittenActivity())
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">W</span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">P</span>
                                        @endif
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $activity->title }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">({{ $activity->total_points }} pts)</span>
                                    </div>
                                </th>
                            @endforeach
                            @if ($this->showFinalGrades)
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-center">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Final Grade</span>
                                </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @php
                            $students = \App\Models\Student::where('team_id', $this->teamId)
                                ->where('status', 'active')
                                ->get();
                        @endphp
                        @foreach ($students as $student)
                            <tr class="fi-ta-row h-5 hover:bg-gray-100 dark:hover:bg-white/10">
                                <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $student->name }}</span>
                                </td>
                                @foreach ($activities as $activity)
                                    @php
                                        $originalScore = $this->activityScores[$student->id][$activity->id] ?? null;
                                    @endphp
                                    <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-center">
                                        <div class="relative">
                                            <input
                                                type="number"
                                                name="scores[{{ $student->id }}][{{ $activity->id }}]"
                                                wire:model="activityScores.{{ $student->id }}.{{ $activity->id }}"
                                                min="0"
                                                max="{{ $activity->total_points }}"
                                                step="0.01"
                                                class="fi-input block w-24 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-70 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 text-center"
                                            />
                                            @if($originalScore != ($this->activityScores[$student->id][$activity->id] ?? null))
                                                <span class="absolute inset-0 flex items-center justify-center text-xs text-red-500">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                                @if ($this->showFinalGrades)
                                    <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-center cursor-pointer" wire:click="openFinalGradeModal('{{ $student->id }}')">
                                        @php
                                            $finalGradeValue = $this->calculateFinalGradeValue($student->id, $activities);
                                        @endphp
                                        @if ($finalGradeValue !== null)
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="@if($finalGradeValue >= 90) text-success-600 dark:text-success-400 @elseif($finalGradeValue >= 80) text-primary-600 dark:text-primary-400 @elseif($finalGradeValue >= 70) text-warning-600 dark:text-warning-400 @else text-danger-600 dark:text-danger-400 @endif font-bold text-lg">
                                                    {{ number_format($finalGradeValue, 2) }}%
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Grading Information Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Grading Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary-100 text-primary-600 dark:bg-primary-900 dark:text-primary-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </span>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Grade Calculation</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Final grades are calculated using weighted averages:
                                <br>
                                • Written activities: <strong>{{ $writtenWeight }}%</strong>
                                <br>
                                • Performance activities: <strong>{{ $performanceWeight }}%</strong>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-success-100 text-success-600 dark:bg-success-900 dark:text-success-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Score Guidelines</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                • Each activity has a maximum score based on total points
                                <br>
                                • Scores can be entered with up to 2 decimal places
                                <br>
                                • Empty scores are not included in calculations
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-warning-100 text-warning-600 dark:bg-warning-900 dark:text-warning-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Important Notes</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                • Grades are automatically saved when updated
                                <br>
                                • You can export grades to CSV for external use
                                <br>
                                • Final grades are calculated in real-time
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-info-100 text-info-600 dark:bg-info-900 dark:text-info-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </span>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Quick Actions</h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                • Click on any score to edit it
                                <br>
                                • View detailed breakdowns in the "View Final Grade" modal
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL FOR FINAL GRADE --}}
    <x-filament::modal id="final-grade-modal" width="2xl">
        <x-slot name="heading">
            Final Grade
        </x-slot>

        <x-slot name="description">
            Detailed breakdown of the student's final grade.
        </x-slot>

        <div id="final-grade-content" class="space-y-4">
            {{-- Content will be loaded here --}}
        </div>
    </x-filament::modal>

    @push('scripts')
        <script>
            // --- Keyboard Navigation ---
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Tab' && e.shiftKey) { // Shift + Tab
                    const currentInput = document.activeElement;
                    if (currentInput.tagName === 'INPUT') {
                        const prevInput = currentInput.parentElement.previousElementSibling?.querySelector('input');
                        if (prevInput) {
                            e.preventDefault();
                            prevInput.focus();
                        }
                    }
                } else if (e.key === 'Tab' && !e.shiftKey) { // Tab
                    const currentInput = document.activeElement;
                    if (currentInput.tagName === 'INPUT') {
                        const nextInput = currentInput.parentElement.nextElementSibling?.querySelector('input');
                        if (nextInput) {
                            e.preventDefault();
                            nextInput.focus();
                        }
                    }
                } else if (e.key === 'Enter') { // Enter
                    const currentInput = document.activeElement;
                    if (currentInput.tagName === 'INPUT') {
                        e.preventDefault();
                        const nextInput = currentInput.parentElement.nextElementSibling?.querySelector('input');
                        if (nextInput) {
                            nextInput.focus();
                        }
                    }
                }
            });

            // --- Final Grade Modal ---
            Livewire.on('openFinalGradeModal', (studentId) => {
                loadFinalGradeContent(studentId);
                $wire.dispatch('open-modal', { id: 'final-grade-modal' });
            });

            function loadFinalGradeContent(studentId) {
                const contentContainer = document.getElementById('final-grade-content');
                contentContainer.innerHTML = `
                <div class="animate-pulse flex flex-col space-y-4">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            `;

                $wire.getStudentScores(studentId).then(data => {
                    const student = data.student;
                    const activities = data.activities;
                    const scores = data.scores || {};

                    const writtenActivities = activities.filter(a => a.category === 'written');
                    const performanceActivities = activities.filter(a => a.category === 'performance');

                    Promise.all([
                        $wire.calculateCategoryAverage(studentId, activities, 'written'),
                        $wire.calculateCategoryAverage(studentId, activities, 'performance'),
                        $wire.calculateFinalGrade(studentId, activities)
                    ]).then(values => {
                        const [writtenAvg, performanceAvg, finalGradeVal] = values;

                        let html = `
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Written Activities</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Weight: {{ $this->writtenWeight }}%</p>
                                    <p class="mt-4 text-2xl font-bold text-primary-600 dark:text-primary-400">${writtenAvg}</p>
                                </div>

                                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                                    <h3 class="text-base font-medium text-gray-900 dark:text-white">Performance Activities</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Weight: {{ $this->performanceWeight }}%</p>
                                    <p class="mt-4 text-2xl font-bold text-primary-600 dark:text-primary-400">${performanceAvg}</p>
                                </div>
                            </div>

                            <div class="rounded-lg bg-gray-100 p-6 dark:bg-gray-700">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Final Grade</h3>
                                <p class="mt-6 text-3xl">${finalGradeVal}</p>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-base font-medium text-gray-900 dark:text-white">Activity Breakdown</h3>
                                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Activity</th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Score</th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">`;

                        activities.forEach(activity => {
                            const score = scores[activity.id] !== undefined ? scores[activity.id] : null;
                            const scoreDisplay = score !== null ? score : '-';
                            const percentageDisplay = score !== null
                                ? (Math.round((score / activity.total_points) * 1000) / 10) + '%'
                                : '-';

                            let percentageColor = '';
                            if (score !== null) {
                                const percentage = (score / activity.total_points) * 100;
                                if (percentage >= 90) {
                                    percentageColor = 'text-success-600 dark:text-success-400';
                                } else if (percentage >= 80) {
                                    percentageColor = 'text-primary-600 dark:text-primary-400';
                                } else if (percentage >= 70) {
                                    percentageColor = 'text-warning-600 dark:text-warning-400';
                                } else {
                                    percentageColor = 'text-danger-600 dark:text-danger-400';
                                }
                            }

                            const categoryBadge = activity.category === 'written'
                                ? '<span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">W</span>'
                                : '<span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">P</span>';

                            html += `
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        ${categoryBadge} ${activity.title}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-medium text-gray-900 dark:text-white">${scoreDisplay}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm text-gray-500 dark:text-gray-400">${activity.total_points}</td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-medium ${percentageColor}">${percentageDisplay}</td>
                                </tr>`;
                        });

                        html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>`;

                        contentContainer.innerHTML = html;
                    });
                }).catch(error => {
                    console.error('Error loading final grade data:', error);
                    contentContainer.innerHTML = `<div class="text-center text-red-500"><p>Error loading final grade data. Please try again.</p></div>`;
                });
            }
        </script>
    @endpush
</x-filament-panels::page>