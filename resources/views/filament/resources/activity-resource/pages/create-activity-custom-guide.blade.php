<x-filament-panels::page
    @class([
        // Add a class to the body when *either* modal is open
        'overflow-hidden' => $showGradingModalStage1 || $showGradingModalStage2,
    ])
>
    {{-- Main container controlling guide visibility and layout --}}
    <div x-data="{
            gradingModal1Visible: @entangle('showGradingModalStage1'),
            gradingModal2Visible: @entangle('showGradingModalStage2'),
            guideIsVisible: @entangle('showGuide')
         }"
         class="relative min-h-[70vh]"
    >

        {{-- Grading System Selection Modal - STAGE 1 (SHS vs College) --}}
        <div x-show="gradingModal1Visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 dark:bg-gray-900/80 backdrop-blur-md"
             aria-labelledby="grading-modal-title-s1"
             role="dialog"
             aria-modal="true"
        >
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                 {{-- Modal Header --}}
                <div class="p-5 sm:p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 id="grading-modal-title-s1" class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                       <x-heroicon-o-scale class="w-6 h-6 text-primary-500"/>
                        Choose Grading System (Step 1/2)
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Select the primary system for <span class="font-medium">{{ $teamName }}</span>.
                    </p>
                </div>
                 {{-- Modal Body - Options --}}
                 <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- SHS Option --}}
                    <button
                        type="button"
                        wire:click="selectShsSystem"
                        wire:loading.attr="disabled" wire:target="selectShsSystem"
                        class="flex flex-col items-center justify-center p-6 text-center bg-white dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-gray-600/50 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-primary-500 transition-all duration-150 group"
                    >
                         <x-heroicon-o-academic-cap class="w-10 h-10 mb-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"/>
                         <span class="font-semibold text-gray-800 dark:text-gray-200">K-12 Senior High</span>
                         <span class="text-xs mt-1 text-gray-500 dark:text-gray-400">Uses WW, PT, QA Components</span>
                         <div wire:loading wire:target="selectShsSystem" class="mt-2"><x-filament::loading-indicator class="w-5 h-5 text-primary-500"/></div>
                    </button>
                    {{-- College Option (Moves to Stage 2) --}}
                     <button
                        type="button"
                        wire:click="selectCollegeSystem"
                        wire:loading.attr="disabled" wire:target="selectCollegeSystem"
                        class="flex flex-col items-center justify-center p-6 text-center bg-white dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-gray-600/50 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-primary-500 transition-all duration-150 group"
                    >
                         <x-heroicon-o-building-library class="w-10 h-10 mb-3 text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"/>
                         <span class="font-semibold text-gray-800 dark:text-gray-200">College / University</span>
                         <span class="text-xs mt-1 text-gray-500 dark:text-gray-400">Uses Term or GWA based grading</span>
                         <div wire:loading wire:target="selectCollegeSystem" class="mt-2"><x-filament::loading-indicator class="w-5 h-5 text-primary-500"/></div>
                    </button>
                 </div>
                 {{-- Footer --}}
                 <div class="px-5 py-3 sm:px-6 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-600 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">This affects available grading options.</p>
                 </div>
            </div>
        </div>

        {{-- Grading System Selection Modal - STAGE 2 (College Scale) --}}
        <div x-show="gradingModal2Visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             x-cloak
             class="fixed inset-0 z-[65] flex items-center justify-center p-4 bg-gray-900/60 dark:bg-gray-900/80 backdrop-blur-md" {{-- Higher z-index --}}
             aria-labelledby="grading-modal-title-s2"
             role="dialog"
             aria-modal="true"
        >
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                 {{-- Modal Header --}}
                 <div class="p-5 sm:p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                     <div>
                        <h3 id="grading-modal-title-s2" class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                           <x-heroicon-o-calculator class="w-6 h-6 text-primary-500"/>
                            Configure College Grading (Step 2/2)
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Choose how grades are calculated and represented for <span class="font-medium">{{ $teamName }}</span>.
                        </p>
                     </div>
                     {{-- Back Button --}}
                     <button type="button"
                             wire:click="goBackToStage1" wire:loading.attr="disabled"
                             class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-1">
                        <x-heroicon-m-arrow-left class="w-4 h-4"/>
                        Back
                    </button>
                 </div>

                 {{-- Modal Body - Options --}}
                 <div class="p-5 sm:p-6 space-y-6">
                    {{-- Term Based Section --}}
                    <div>
                        <h4 class="text-base font-medium text-gray-800 dark:text-gray-200 mb-3 border-b pb-2 dark:border-gray-600">Term-Based Grading</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach ($collegeTermScales as $scale)
                                @php
                                    $scaleInfo = match($scale) {
                                        \App\Models\Team::COLLEGE_SCALE_TERM_5_POINT => ['name' => '5-Point Scale', 'desc' => 'e.g., 1.00 - 5.00'],
                                        \App\Models\Team::COLLEGE_SCALE_TERM_4_POINT => ['name' => '4-Point Scale', 'desc' => 'e.g., 4.0 - 0.0'],
                                        \App\Models\Team::COLLEGE_SCALE_TERM_PERCENTAGE => ['name' => 'Percentage', 'desc' => 'e.g., 100% - 0%'],
                                        default => ['name' => $scale, 'desc' => '']
                                    };
                                @endphp
                                <button
                                    type="button"
                                    wire:click="setCollegeScale('{{ $scale }}')"
                                    wire:loading.attr="disabled" wire:target="setCollegeScale"
                                    class="p-4 text-center bg-white dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-gray-600/50 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-primary-500 transition-all duration-150 group"
                                >
                                    <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $scaleInfo['name'] }}</span>
                                    <span class="block text-xs mt-1 text-gray-500 dark:text-gray-400">{{ $scaleInfo['desc'] }}</span>
                                </button>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Calculates Prelim, Midterm, and Final grades separately, then combines them using weights.</p>
                    </div>

                    {{-- GWA Based Section --}}
                     <div>
                        <h4 class="text-base font-medium text-gray-800 dark:text-gray-200 mb-3 border-b pb-2 dark:border-gray-600">GWA-Based Grading</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                             @foreach ($collegeGwaScales as $scale)
                                @php
                                    $scaleInfo = match($scale) {
                                        \App\Models\Team::COLLEGE_SCALE_GWA_5_POINT => ['name' => '5-Point Scale', 'desc' => 'e.g., 1.00 - 5.00'],
                                        \App\Models\Team::COLLEGE_SCALE_GWA_4_POINT => ['name' => '4-Point Scale', 'desc' => 'e.g., 4.0 - 0.0'],
                                        \App\Models\Team::COLLEGE_SCALE_GWA_PERCENTAGE => ['name' => 'Percentage', 'desc' => 'e.g., 100% - 0%'],
                                        default => ['name' => $scale, 'desc' => '']
                                    };
                                @endphp
                                <button
                                    type="button"
                                    wire:click="setCollegeScale('{{ $scale }}')"
                                    wire:loading.attr="disabled" wire:target="setCollegeScale"
                                    class="p-4 text-center bg-white dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:border-primary-500 dark:hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-gray-600/50 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-primary-500 transition-all duration-150 group"
                                >
                                    <span class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $scaleInfo['name'] }}</span>
                                    <span class="block text-xs mt-1 text-gray-500 dark:text-gray-400">{{ $scaleInfo['desc'] }}</span>
                                </button>
                            @endforeach
                        </div>
                         <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Calculates a final Grade Weight Average (GWA) based on activity grades and their credit units.</p>
                    </div>
                 </div>

                 {{-- Loading Indicator --}}
                 <div wire:loading wire:target="setCollegeScale" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center rounded-xl">
                     <x-filament::loading-indicator class="w-10 h-10" />
                 </div>
            </div>
        </div>

        {{-- Dimming Overlay for Guide Panel --}}
        {{-- Show only if guideIsVisible is true AND *both* modals are hidden --}}
        <div x-show="guideIsVisible && !gradingModal1Visible && !gradingModal2Visible"
             x-transition:enter="transition-opacity ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="fixed inset-0 z-[45] bg-gray-900/20 dark:bg-gray-900/40 backdrop-blur-sm"
             @click="$wire.dismissGuide()">
        </div>

        {{-- Main Content Area (Form + Guide Panel Space) --}}
        {{-- Show only when *both* grading modals are hidden --}}
        <div x-show="!gradingModal1Visible && !gradingModal2Visible"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="transition-all duration-500 ease-in-out"
             :class="{ 'lg:pr-[26rem]': guideIsVisible }"
        >
            {{-- Form Area --}}
             <div class="relative z-[48] transition-all duration-500 ease-in-out"
                 :class="{ 'opacity-70 scale-[0.98] lg:opacity-100 lg:scale-100 pointer-events-none lg:pointer-events-auto': guideIsVisible }"
            >
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700">
                    <x-filament-panels::form wire:submit="create" class="p-4 sm:p-6">
                        {{ $this->form }}
                        <x-filament-panels::form.actions
                            :actions="$this->getFormActions()"
                            class="px-6 pb-6"
                        />
                    </x-filament-panels::form>
                </div>
            </div>
        </div>

        {{-- Guide Side Panel --}}
        {{-- Show only if guideIsVisible is true AND *both* modals are hidden --}}
        <div x-show="guideIsVisible && !gradingModal1Visible && !gradingModal2Visible"
             x-transition:enter="transition ease-in-out duration-500 transform"
             x-transition:enter-start="opacity-0 translate-x-full"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-full"
             x-cloak
             class="fixed inset-y-0 right-0 z-50 w-full max-w-md lg:max-w-[24rem] h-full bg-gray-50 dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 shadow-2xl flex flex-col"
             aria-labelledby="guide-panel-title"
             role="complementary"
        >
             {{-- Panel Header --}}
             <div class="flex items-center justify-between p-4 sm:p-5 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 id="guide-panel-title" class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                     <x-heroicon-o-information-circle class="w-5 h-5 text-primary-500"/>
                     Activity Creation Guide
                 </h3>
                 <button type="button" @click="$wire.dismissGuide()" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 p-1 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500" aria-label="Dismiss Guide">
                    <x-heroicon-o-x-mark class="w-5 h-5"/>
                 </button>
             </div>
             {{-- Scrollable Panel Content (remains the same) --}}
            <div class="flex-grow overflow-y-auto p-4 sm:p-6 prose prose-sm dark:prose-invert max-w-none prose-headings:text-primary-600 dark:prose-headings:text-primary-400 prose-a:text-primary-600 dark:prose-a:text-primary-400">
                 <h4>Welcome!</h4><p>Let's create a new learning activity. This panel guides you through the form sections.</p><hr class="dark:border-gray-600"/><h5><x-heroicon-o-clipboard-document-list class="inline-block w-4 h-4 mr-1" /> Details Tab</h5><p>Start with the basics:</p><ul><li><strong>Title:</strong> A clear name for the activity.</li><li><strong>Activity Type:</strong> Choose from Quiz, Assignment, Project, etc.</li><li><strong>Status:</strong> 'Draft' (hidden) or 'Published' (visible to students).</li><li><strong>Description & Instructions:</strong> Provide context and clear steps for students.</li></ul><h5><x-heroicon-o-cog-6-tooth class="inline-block w-4 h-4 mr-1" /> Configuration Tab</h5><p>Define how the activity works and is graded:</p><ul><li><strong>Mode:</strong> Individual, Group, or Take-Home.</li><li><strong>Grading Component:</strong><ul><li><em>SHS:</em> Select Written Work (WW), Performance Task (PT), or Quarterly Assessment (QA).</li><li><em>College (Term):</em> Choose Prelim, Midterm, or Final.</li> <li><em>College (GWA):</em> Enter the Credit Units.</li></ul></li> <li><strong>Total Points:</strong> The maximum score achievable.</li> <li><strong>Format:</strong> A general category (Quiz, Assignment, etc.).</li></ul><h5><x-heroicon-o-arrow-down-tray class="inline-block w-4 h-4 mr-1" /> Submission Settings Tab</h5><p>Determine how students submit:</p><ul><li><strong>File Upload / Text Entry:</strong> Students upload files or type directly. Configure allowed types/size.</li><li><strong>Online Form:</strong> Build a structured form with questions.</li> <li><strong>Manual Grading:</strong> No online submission required.</li></ul><h5><x-heroicon-o-paper-clip class="inline-block w-4 h-4 mr-1" /> Resources (on Edit)</h5><p>You can attach supporting files (like worksheets or readings) for students <span class="italic">after saving</span> the activity, when you come back to edit it.</p>
             </div>
             {{-- Panel Footer / Action --}}
             <div class="p-4 sm:p-5 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                 <x-filament::button color="primary" wire:loading.attr="disabled" wire:target="dismissGuide" tag="button" type="button" icon="heroicon-o-check-circle" @click="$wire.dismissGuide()" class="w-full">
                     Got it!
                 </x-filament::button>
             </div>
        </div>

    </div> {{-- End x-data container --}}
</x-filament-panels::page>
