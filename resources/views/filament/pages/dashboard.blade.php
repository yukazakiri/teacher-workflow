<x-filament-panels::page>

{{-- @mingles --}}
    {{-- @livewire('chat', ['conversationId' => $conversationId ?? null]) --}}
@livewire('ai-widget')

    @if($onboardingState > 0)
        <div
            x-data="{ open: true }"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto p-4 pt-[10vh] sm:pt-0 flex items-start sm:items-center justify-center bg-gray-900/50 dark:bg-gray-900/75"
            @keydown.escape.window="open = false; $wire.markOnboardingStepComplete({{ $onboardingState }})" {{-- Mark complete on Esc --}}
            aria-labelledby="onboarding-modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="w-full max-w-xl bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden flex flex-col max-h-[85vh]"
                x-trap.inert.noscroll="open"
                @click.outside="open = false; $wire.markOnboardingStepComplete({{ $onboardingState }})" {{-- Mark complete if clicked outside --}}
            >
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-shrink-0">
                    <h3 id="onboarding-modal-title" class="text-lg font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                        @if($onboardingState === 1)
                            <x-heroicon-o-sparkles class="h-6 w-6 text-primary-500" />
                            <span>Welcome to Your New Class!</span>
                        @elseif($onboardingState === 2)
                            <x-heroicon-o-rocket-launch class="h-6 w-6 text-primary-500" />
                            <span>Great Progress! What's Next?</span>
                        @endif
                    </h3>
                    {{-- Close button marks the current step as seen --}}
                    <button
                        x-tooltip.raw.left="'Close and mark as seen'" {{-- Added tooltip for clarity --}}
                        @click="open = false; $wire.markOnboardingStepComplete({{ $onboardingState }})"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded-full p-1"
                        aria-label="Close Onboarding"
                        type="button" {{-- Added type="button" --}}
                     >
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- Modal Body (Scrollable) --}}
                <div class="px-6 py-6 overflow-y-auto space-y-6">
                    {{-- State 1: Add Students --}}
                    @if($onboardingState === 1)
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 p-3 bg-primary-100 dark:bg-primary-500/20 rounded-full">
                                 <x-heroicon-o-user-plus class="h-10 w-10 text-primary-600 dark:text-primary-400" />
                            </div>
                            <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Let's Get Your Class Set Up</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Start by adding students to manage assignments and track progress.</p>
                        </div>

                        <div class="space-y-3">
                            {{-- Step 1 (Current) --}}
                            <div class="bg-primary-50 dark:bg-primary-700/20 rounded-lg p-4 border border-primary-200 dark:border-primary-600 ring-2 ring-primary-500/50 dark:ring-primary-500/70 shadow-sm">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-primary-500 text-white font-bold text-xs mt-0.5">1</div>
                                    <div>
                                        <h5 class="font-semibold text-gray-900 dark:text-white">Add Your Students</h5>
                                        <p class="text-sm text-primary-800 dark:text-primary-200/90">Invite students via email, import from a file, or add manually.</p>
                                    </div>
                                </div>
                            </div>
                            {{-- Step 2 (Future) --}}
                            <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-600 opacity-70">
                               <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 dark:bg-gray-500 text-white font-bold text-xs mt-0.5">2</div>
                                    <div>
                                        <h5 class="font-medium text-gray-600 dark:text-gray-300">Create Learning Activities</h5>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Assign tasks, quizzes, or discussions once students are added.</p>
                                    </div>
                                </div>
                            </div>
                             {{-- Step 3 (Future - Example) --}}
                             <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-600 opacity-70">
                               <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 dark:bg-gray-500 text-white font-bold text-xs mt-0.5">3</div>
                                    <div>
                                        <h5 class="font-medium text-gray-600 dark:text-gray-300">Upload Learning Resources</h5>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Share materials like documents or links.</p>
                                    </div>
                               </div>
                            </div>
                        </div>
                    @endif

                    {{-- State 2: Create Activities --}}
                    @if($onboardingState === 2)
                        <div class="flex flex-col items-center text-center">
                            <div class="mb-4 p-3 bg-primary-100 dark:bg-primary-500/20 rounded-full">
                                 <x-heroicon-o-pencil-square class="h-10 w-10 text-primary-600 dark:text-primary-400" />
                            </div>
                            <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Time to Engage Your Students</h4>
                             <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">You've added {{ $studentThreshold }} or more students! Now, let's create some activities.</p>
                        </div>

                        <div class="space-y-3">
                             {{-- Step 1 (Done) --}}
                            <div class="bg-green-50 dark:bg-green-700/20 rounded-lg p-4 border border-green-200 dark:border-green-600 opacity-80">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white mt-0.5">
                                        <x-heroicon-s-check class="w-4 h-4"/>
                                    </div>
                                    <div>
                                        <h5 class="font-medium text-gray-700 dark:text-gray-200 line-through">Add Your Students</h5>
                                        <p class="text-sm text-green-700 dark:text-green-300/90">Great job adding students!</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Step 2 (Current) --}}
                            <div class="bg-primary-50 dark:bg-primary-700/20 rounded-lg p-4 border border-primary-200 dark:border-primary-600 ring-2 ring-primary-500/50 dark:ring-primary-500/70 shadow-sm">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-primary-500 text-white font-bold text-xs mt-0.5">2</div>
                                    <div>
                                        <h5 class="font-semibold text-gray-900 dark:text-white">Create Learning Activities</h5>
                                        <p class="text-sm text-primary-800 dark:text-primary-200/90">Design assignments, quizzes, or other tasks.</p>
                                    </div>
                                </div>
                            </div>

                             {{-- Step 3 (Future - Example) --}}
                             <div class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-600 opacity-70">
                               <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 dark:bg-gray-500 text-white font-bold text-xs mt-0.5">3</div>
                                    <div>
                                        <h5 class="font-medium text-gray-600 dark:text-gray-300">Upload Learning Resources</h5>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Share materials later to support learning.</p>
                                    </div>
                               </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
                     {{-- Responsive Button Layout: Stacked on small, side-by-side on larger --}}
                    <div class="flex flex-col-reverse sm:flex-row sm:justify-between sm:items-center space-y-2 space-y-reverse sm:space-y-0 sm:space-x-3">
                        <x-filament::button
                            color="gray"
                            tag="button"
                            type="button"
                            @click="open = false; $wire.markOnboardingStepComplete({{ $onboardingState }})"
                            class="w-full sm:w-auto" {{-- Full width on mobile --}}
                        >
                            I'll do this later
                        </x-filament::button>

                         @if($onboardingState === 1)
                           <x-filament::button
                               color="primary"
                               tag="a"
                               :href="$studentResourceCreateUrl" {{-- Use correct variable --}}
                               {{-- Clicking the link implies completing the step, mark it. Also close modal. --}}
                               @click="open = false; setTimeout(() => $wire.markOnboardingStepComplete(1), 50)"
                               class="w-full sm:w-auto" {{-- Full width on mobile --}}
                               icon="heroicon-o-user-plus"
                            >
                               Add Students Now
                           </x-filament::button>
                         @elseif ($onboardingState === 2)
                            <x-filament::button
                               color="primary"
                               tag="a"
                               :href="$activityResourceCreateUrl" {{-- Use correct variable --}}
                               {{-- Clicking the link implies completing the step, mark it. Also close modal. --}}
                               @click="open = false; setTimeout(() => $wire.markOnboardingStepComplete(2), 50)"
                               class="w-full sm:w-auto" {{-- Full width on mobile --}}
                               icon="heroicon-o-pencil-square"
                            >
                               Create Activity Now
                           </x-filament::button>
                         @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
