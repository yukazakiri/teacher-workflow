@php
use Illuminate\Support\Str;
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
                 @if ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_SHS)
                     <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                         <div class="flex items-center gap-1.5">
                             <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs font-bold">WW</span>
                             <span>Written Work</span>
                         </div>
                         <div class="flex items-center gap-1.5">
                             <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 text-xs font-bold">PT</span>
                             <span>Performance Task</span>
                         </div>
                         <div class="flex items-center gap-1.5">
                             <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 text-xs font-bold">QA</span>
                             <span>Quarterly Assessment</span>
                         </div>
                     </div>
                 @elseif ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_COLLEGE)
                     <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300">
                         <div class="flex items-center gap-1.5">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                             </svg>
                             <span>GWA calculation based on Credit Units</span>
                         </div>
                     </div>
                 @endif
            </div>
        </div>

        {{-- Grading Configuration Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
            {{ $this->form }}
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
                            <svg wire:loading wire:target="saveAllScores" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                              </svg>
                            <svg wire:loading.remove wire:target="saveAllScores" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Save All Scores
                        </button>

                        {{-- Grade Color Legend --}}
                        <div class="hidden sm:flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 border-l pl-3 ml-3 border-gray-200 dark:border-gray-600">
                             {{-- Dynamically show legend based on system --}}
                             @php
                                                     $legendItems = [];
                                                     $numericScale = $team?->getCollegeNumericScale(); // Get the underlying scale type

                                                     if ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_SHS) {
                                                         $legendItems = [
                                                             ['label' => '≥90', 'color' => 'success'], ['label' => '≥85', 'color' => 'primary'],
                                                             ['label' => '≥80', 'color' => 'info'], ['label' => '≥75', 'color' => 'warning'], ['label' => '<75', 'color' => 'danger'],
                                                         ];
                                                     } elseif ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_COLLEGE && $numericScale === 'percentage') { // <--- CORRECTED LINE
                                                         $legendItems = [
                                                             ['label' => '≥90%', 'color' => 'success'], ['label' => '≥80%', 'color' => 'primary'],
                                                             ['label' => '≥75%', 'color' => 'warning'], ['label' => '<75%', 'color' => 'danger'],
                                                         ];
                                                     } elseif ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_COLLEGE && $numericScale === '5_point') { // Added 5-point legend
                                                          $legendItems = [
                                                              ['label' => '≤1.50', 'color' => 'success'], ['label' => '≤2.00', 'color' => 'primary'],
                                                              ['label' => '≤2.50', 'color' => 'info'], ['label' => '≤3.00', 'color' => 'warning'], ['label' => '>3.00', 'color' => 'danger'],
                                                          ];
                                                     } elseif ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_COLLEGE && $numericScale === '4_point') { // Added 4-point legend
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
                 {{-- Display validation errors related to score inputs --}}
                 @if ($errors->has('activityScores.*'))
                     <div class="mt-3 text-sm text-danger-600 dark:text-danger-400">
                         Please correct the score input errors highlighted below. Scores cannot be negative or exceed the activity's total points.
                     </div>
                 @endif
            </div>

            {{-- Grades Table --}}
            <div class="overflow-x-auto">
                @if ($students->isEmpty())
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">No active students found in this team.</div>
                @elseif ($activities->isEmpty())
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">No published activities found for this team.</div>
                @else
                    <table class="w-full fi-ta-table divide-y divide-gray-200 dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5 sticky top-0 z-10">
                            <tr class="fi-ta-header-row">
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start whitespace-nowrap">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Student Name</span>
                                </th>
                                {{-- Activity Headers --}}
                                @foreach ($activities as $activity)
                                    <th class="fi-ta-header-cell px-3 py-3.5 text-center whitespace-nowrap">
                                        <div class="flex flex-col items-center">
                                             {{-- SHS Component Badge --}}
                                             @if ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_SHS && $activity->component_type)
                                                 <span @class([
                                                    'inline-flex items-center justify-center w-6 h-6 rounded-md text-xs font-bold mb-1',
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $activity->component_type === \App\Models\Activity::COMPONENT_WRITTEN_WORK,
                                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $activity->component_type === \App\Models\Activity::COMPONENT_PERFORMANCE_TASK,
                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' => $activity->component_type === \App\Models\Activity::COMPONENT_QUARTERLY_ASSESSMENT,
                                                 ])>
                                                     {{ $activity->component_type_code }}
                                                 </span>
                                             @endif
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $activity->title }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                ({{ $activity->total_points ?? 'N/A' }} pts
                                                @if ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_COLLEGE && $activity->credit_units > 0)
                                                    / {{ $activity->credit_units }} units
                                                @endif
                                                )
                                            </span>
                                        </div>
                                    </th>
                                @endforeach
                                {{-- Overall Grade Header --}}
                                @if ($showFinalGrades)
                                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-center whitespace-nowrap">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            @if ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_SHS)
                                                Overall Grade
                                            @elseif ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_COLLEGE)
                                                GWA / Avg
                                            @else
                                                Overall Grade
                                            @endif
                                        </span>
                                         <span class="block text-xs text-gray-400 dark:text-gray-500">(Click to View)</span>
                                    </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @foreach ($students as $student)
                                <tr class="fi-ta-row h-16 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-75">
                                    <td class="fi-ta-cell px-3 py-2 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $student->name }}</span>
                                    </td>
                                    {{-- Score Inputs --}}
                                    @foreach ($activities as $activity)
                                         @php
                                             // Generate a unique key for the wire:model.defer
                                             $wireModelKey = "activityScores.{$student->id}.{$activity->id}";
                                         @endphp
                                        <td class="fi-ta-cell px-3 py-2 text-center">
                                            <input
                                                type="number"
                                                {{-- Use wire:model.defer to only update on save --}}
                                                wire:model.defer="{{ $wireModelKey }}"
                                                min="0"
                                                @if($activity->total_points !== null) max="{{ $activity->total_points }}" @endif
                                                step="0.01" {{-- Allow decimals --}}
                                                placeholder="-"
                                                aria-label="Score for {{ $student->name }} on {{ $activity->title }}"
                                                class="fi-input block w-20 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-70 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:border-gray-600 text-center text-sm py-1.5 px-1
                                                       @error($wireModelKey) !border-danger-600 dark:!border-danger-500 @enderror" {{-- Highlight on error --}}
                                            />
                                             {{-- Display validation error message inline --}}
                                            @error($wireModelKey)
                                                <p class="mt-1 text-xs text-danger-600 dark:text-danger-400">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    @endforeach
                                    {{-- Overall Grade Display --}}
                                    @if ($showFinalGrades)
                                        <td class="fi-ta-cell px-3 py-2 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            {{-- Use wire:click for modal --}}
                                            wire:click="openFinalGradeModal('{{ $student->id }}')"
                                            title="Click to view grade breakdown for {{ $student->name }}">
                                            {{-- Use the new formatted grade method --}}
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
                 @if ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_SHS)
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
                         <p class="mt-4 text-xs text-gray-500">* Overall grade shown in the table is the transmuted grade based on the calculated initial grade from all entered scores.</p>
                     </div>
                 @elseif ($gradingSystemType === \App\Models\Team::GRADING_SYSTEM_COLLEGE)
                     <div class="space-y-3">
                         <p><strong>System:</strong> College/University</p>
                         <p><strong>Scale:</strong> {{ $collegeGradingScale ? str_replace('_', ' ', Str::title($collegeGradingScale)) : 'Not Set' }}</p>
                         <p><strong>Calculation (GWA/Average):</strong></p>
                         <ol class="list-decimal list-inside ml-4 space-y-1">
                             <li>For each activity with a score and positive credit units:</li>
                             <li class="ml-4">Calculate percentage: (Score / Total Points) * 100.</li>
                             <li class="ml-4">Convert percentage to the selected grading scale value (e.g., 4.0, 1.75, 85.00).</li>
                             <li class="ml-4">Multiply scale grade by activity's credit units.</li>
                             <li>Sum all weighted grades (Scale Grade * Units).</li>
                             <li>Sum all credit units for activities included.</li>
                             <li>Divide total weighted grades by total credit units.</li>
                         </ol>
                         <p class="mt-2 text-xs text-gray-500">* The specific percentage-to-scale conversion used here is a general example. Actual university conversions vary widely.</p>
                     </div>
                      <div class="space-y-3">
                         <p><strong>Credit Units:</strong> Activities must have credit units assigned (via Activity settings) to be included in the GWA calculation.</p>
                         <p><strong>Passing Grade:</strong> Varies significantly by institution and scale (e.g., 3.00 on 5-pt, 1.0/2.0 on 4-pt, 75% on percentage).</p>
                         <p class="mt-4 text-xs text-gray-500">* Overall grade shown in the table is the calculated GWA or average based on the selected scale and entered scores/units.</p>
                     </div>
                 @else
                     <div class="text-center text-gray-500 col-span-2">Please configure the grading system for this team above.</div>
                 @endif
            </div>
        </div>

    </div> {{-- End main space-y-6 --}}

    {{-- MODAL FOR FINAL GRADE --}}
     {{-- Use wire:ignore.self to prevent Livewire from interfering with modal state managed by JS --}}
     <div wire:ignore.self>
         <x-filament::modal id="final-grade-modal" width="3xl"> {{-- Wider modal --}}
             <x-slot name="heading">
                 Grade Breakdown (<span id="modal-student-name">Student</span>)
             </x-slot>

             <x-slot name="description">
                 Detailed calculation for the selected grading system.
             </x-slot>

             {{-- Content Area --}}
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
                 {{-- Content will be loaded here by JS --}}
             </div>

             <x-slot name="footer">
                 <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'final-grade-modal' })">
                     Close
                 </x-filament::button>
            </x-slot>

         </x-filament::modal>
     </div>


    {{-- Scripts --}}
    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {

                 // --- Keyboard Navigation (Optional - Keep or remove as needed) ---
                 // Basic Tab/Enter navigation can often be handled by browser defaults
                 // document.addEventListener('keydown', function(e) { ... });

                 // --- Final Grade Modal ---
                 let currentStudentId = null; // Keep track of the student ID for the open modal

                 // Listen for the open-modal event dispatched from PHP
                 Livewire.on('open-modal', (event) => {
                     if (event.id === 'final-grade-modal' && event.studentId) {
                         console.log('Opening modal for student:', event.studentId);
                         currentStudentId = event.studentId;
                         loadFinalGradeContent(currentStudentId);
                         // Open the modal using Filament's JS helper or Alpine directly
                         // Assuming Alpine is available: $dispatch('open-modal', { id: 'final-grade-modal' });
                         // Check Filament documentation for the preferred way if not using Alpine directly
                     }
                 });

                 // Function to load and render modal content
                 function loadFinalGradeContent(studentId) {
                         // ... get elements, show loading ...
                         const contentContainer = document.getElementById('final-grade-content');
                         const loadingIndicator = document.getElementById('modal-loading-state');
                         const studentNameSpan = document.getElementById('modal-student-name');
                         loadingIndicator.style.display = 'block';
                         contentContainer.querySelectorAll(':not(#modal-loading-state)').forEach(el => el.remove());
                         studentNameSpan.textContent = 'Loading...';


                         @this.getStudentGradeBreakdown(studentId)
                             .then(result => {
                                 loadingIndicator.style.display = 'none';
                                 if (result.error) { /* ... handle error ... */ return; }
                                 studentNameSpan.textContent = result.student?.name || 'Student';

                                 // --- USE CORRECT RENDER FUNCTION ---
                                 if (result.breakdown?.type === 'shs') {
                                     renderShsModalContent(contentContainer, result);
                                 } else if (result.breakdown?.type === 'college_term') { // Check for new type
                                     renderCollegeTermModalContent(contentContainer, result);
                                 } else if (result.breakdown?.type === 'college_gwa') { // Check for new type
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

                 // Function to render SHS breakdown
                 function renderShsModalContent(container, data) {
                        const breakdown = data.breakdown;
                        const activities = data.activities;

                        // Use pre-formatted values from breakdown.components
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
                                   return `
                                       <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                               ${act.component_code ? `<span class="legend-badge ${
                                                   act.component_type === 'written_work' ? 'bg-blue-100 ...' :
                                                   act.component_type === 'performance_task' ? 'bg-red-100 ...' :
                                                   act.component_type === 'quarterly_assessment' ? 'bg-yellow-100 ...' : ''
                                               }">${act.component_code}</span>` : ''}
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

                      let html = `
                                  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
                                      ${renderComponent(breakdown.components.ww, breakdown.weights.ww, 'Written Work', 'WW', 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200')}
                                      ${renderComponent(breakdown.components.pt, breakdown.weights.pt, 'Performance Task', 'PT', 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200')}
                                      ${renderComponent(breakdown.components.qa, breakdown.weights.qa, 'Quarterly Assessment', 'QA', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300')}
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
                                                  ${[...wwActivities, ...ptActivities, ...qaActivities].map(getActivityRow).join('')}
                                                  ${![...wwActivities, ...ptActivities, ...qaActivities].length ? '<tr><td colspan="4" class="text-center py-4 text-gray-500">No activities with scores found.</td></tr>' : ''}
                                              </tbody>
                                          </table>
                                      </div>
                                  </div>
                              `;
                              container.innerHTML = html;
                          }

                          // Updated Function to render College Term-Based breakdown
                              function renderCollegeTermModalContent(container, data) {
                                  const breakdown = data.breakdown;
                                  const activities = data.activities;
                                  const formattedTermGrades = breakdown.formatted_term_grades || {};
                                  const termGradeColors = breakdown.term_grade_colors || {};

                                  const renderTermCard = (termKey, termLabel, weight, termBadgeClass) => {
                                      const grade = formattedTermGrades[termKey] || 'N/A';
                                      const color = termGradeColors[termKey] || 'text-gray-400';
                                      return `
                                          <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4 border dark:border-gray-700">
                                              <h4 class="text-base font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                                 <span class="legend-badge ${termBadgeClass}">${termKey.substring(0,3).toUpperCase()}</span>
                                                 ${termLabel} (${weight}%)
                                              </h4>
                                              <p class="mt-4 text-2xl font-bold ${color}">${grade}</p>
                                              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg. % converted to scale</p>
                                          </div>`;
                                  };

                                  const getActivityRow = (act) => {
                                      if (!act.term) return ''; // Only show term activities
                                      const score = act.score !== null ? act.score : '-';
                                      return `
                                          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                              <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                                  <span class="legend-badge ${
                                                      act.term === 'prelim' ? 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300' :
                                                      act.term === 'midterm' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' :
                                                      act.term === 'final' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300' : ''
                                                  }">${act.term_code}</span>
                                                  ${act.title}
                                              </td>
                                              <td class="px-3 py-1.5 text-right font-medium">${score}</td>
                                              <td class="px-3 py-1.5 text-right text-gray-500 dark:text-gray-400">${act.total_points ?? 'N/A'}</td>
                                              <td class="px-3 py-1.5 text-right font-medium ${act.color_class}">${act.formatted_percentage}</td>
                                          </tr>`;
                                  };
                                  const prelimActivities = activities.filter(a => a.term === 'prelim');
                                          const midtermActivities = activities.filter(a => a.term === 'midterm');
                                          const finalActivities = activities.filter(a => a.term === 'final');

                                          // Helper to calculate weighted part display for the final grade box
                                                  const calculateWeightedPart = (termKey) => {
                                                      const grade = breakdown.term_grades ? breakdown.term_grades[termKey] : null; // Use raw grade for calc
                                                      const weight = breakdown.weights ? breakdown.weights[termKey] : 0;
                                                      return grade !== null ? (grade * (weight / 100)).toFixed(2) : 'N/A';
                                                  };
                                                  let html = `
                                                              <p class="text-xs text-gray-500 dark:text-gray-400">${breakdown.scale_description}</p>
                                                              {{-- Term Grade Summary --}}
                                                              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                                                                  ${renderTermCard('prelim', 'Prelim Grade', breakdown.weights.prelim, 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300')}
                                                                  ${renderTermCard('midterm', 'Midterm Grade', breakdown.weights.midterm, 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300')}
                                                                  ${renderTermCard('final', 'Final Term Grade', breakdown.weights.final, 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300')}
                                                              </div>

                                                              {{-- Final Grade Calculation Box --}}
                                                              <div class="rounded-lg bg-gray-100 dark:bg-gray-700/50 p-4 border dark:border-gray-600">
                                                                  <h4 class="text-base font-medium text-gray-900 dark:text-white">Final Grade Calculation</h4>
                                                                  <div class="mt-3 text-sm space-y-1">
                                                                      <div class="flex justify-between"><span>(Prelim Grade × ${breakdown.weights.prelim}%)</span> <span>${calculateWeightedPart('prelim')}</span></div>
                                                                      <div class="flex justify-between"><span>+ (Midterm Grade × ${breakdown.weights.midterm}%)</span> <span>${calculateWeightedPart('midterm')}</span></div>
                                                                      <div class="flex justify-between border-b pb-1 dark:border-gray-600"><span>+ (Final Term Grade × ${breakdown.weights.final}%)</span> <span>${calculateWeightedPart('final')}</span></div>
                                                                      <div class="flex justify-between font-bold pt-1">
                                                                          <span>= Final Grade</span>
                                                                          <span class="text-xl ${breakdown.final_grade_color}">${breakdown.formatted_final_grade}</span>
                                                                      </div>
                                                                      <p class="text-xs text-gray-500 dark:text-gray-400 pt-2">* Weights adjusted proportionally if a term has no score.</p>
                                                                  </div>
                                                              </div>

                                                              {{-- Activity Breakdown Table --}}
                                                              <div class="space-y-4">
                                                                  <h4 class="text-base font-medium text-gray-900 dark:text-white mt-4">Activity Scores by Term</h4>
                                                                  <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto">
                                                                      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                                           <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                                                              <tr>
                                                                                  <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Activity (Term)</th>
                                                                                  <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Score</th>
                                                                                  <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                                                                  <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">%</th>
                                                                              </tr>
                                                                          </thead>
                                                                          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900/50">
                                                                              ${[...prelimActivities, ...midtermActivities, ...finalActivities].map(getActivityRow).join('')}
                                                                              ${![...prelimActivities, ...midtermActivities, ...finalActivities].length ? '<tr><td colspan="4" class="text-center py-4 text-gray-500">No activities with scores found for any term.</td></tr>' : ''}
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
                                                              const activityGrades = breakdown.activity_grades || {}; // Use the formatted ones

                                                              const getActivityRow = (act) => {
                                                                  const score = act.score !== null ? act.score : '-';
                                                                  const units = act.credit_units > 0 ? act.credit_units.toFixed(2) : '-';
                                                                  const actGradeInfo = activityGrades[act.id]; // Get pre-formatted info

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
                                                                      <p class="text-xs text-gray-500 dark:text-gray-400">${breakdown.scale_description}</p>
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
                                                                       <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 max-h-96 overflow-y-auto">
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
                                                                                  ${activities.map(getActivityRow).join('')}
                                                                                  ${!Object.keys(activityGrades).length ? '<tr><td colspan="5" class="text-center py-4 text-gray-500">No activities with scores and positive units found for GWA calculation.</td></tr>' : ''}
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
