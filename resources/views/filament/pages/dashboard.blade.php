<x-filament-panels::page>


        @livewire('chat', ['conversationId' => $conversationId ?? null])


        @if($onboardingState > 0)
           <div
               x-data="{ open: true }"
               x-show="open"
               x-cloak
               class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 dark:bg-gray-900/75"
               {{-- Close modal on escape key press --}}
               @keydown.escape.window="open = false"
           >
               <div
                   x-show="open"
                   x-transition:enter="transition ease-out duration-300"
                   x-transition:enter-start="opacity-0 transform scale-95"
                   x-transition:enter-end="opacity-100 transform scale-100"
                   x-transition:leave="transition ease-in duration-200"
                   x-transition:leave-start="opacity-100 transform scale-100"
                   x-transition:leave-end="opacity-0 transform scale-95"
                   class="w-full max-w-2xl bg-background dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden"
                   {{-- Trap focus within the modal --}}
                   x-trap.inert.noscroll="open"
                   {{-- Close modal when clicking outside --}}
                   @click.outside="open = false"
               >
                   {{-- State 1: Add Students --}}
                   @if($onboardingState === 1)
                       <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                           <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                               <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                               Welcome to Your New Class!
                           </h3>
                           {{-- Close button also marks step 1 as seen --}}
                           <button @click="open = false" wire:click="markOnboardingStepComplete(1)" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                               <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                           </button>
                       </div>
                       <div class="px-6 py-4">
                           <div class="flex items-center justify-center mb-6">
                               <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                               </div>
                           </div>
                           <h4 class="text-lg font-medium text-gray-900 dark:text-white text-center mb-4">Let's Get Started with Your Class</h4>
                           <div class="text-sm text-gray-500 dark:text-gray-400 space-y-4">
                               <p>Congratulations on creating your new class! To get the most out of the platform, we recommend starting with these steps:</p>
                               <div class="bg-primary-50 dark:bg-primary-700/50 rounded-lg p-4 border border-primary-200 dark:border-primary-700"> {{-- Highlight Step 1 --}}
                                   <h5 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                       <span class="flex items-center justify-center w-6 h-6 rounded-full bg-primary-500 text-white mr-2 text-xs">1</span>
                                       Add Your Students
                                   </h5>
                                   <p class="ml-8">Begin by adding your students to the class. This will allow you to track their progress, assign activities, and manage their learning journey.</p>
                               </div>
                               <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 opacity-60"> {{-- De-emphasize Step 2 --}}
                                   <h5 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                       <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-500 text-white mr-2 text-xs">2</span>
                                       Create Learning Activities
                                   </h5>
                                   <p class="ml-8">After adding students, you can create learning activities, assignments, and exams for your class.</p>
                               </div>
                               <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 opacity-60"> {{-- De-emphasize Step 3 --}}
                                   <h5 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                       <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-500 text-white mr-2 text-xs">3</span>
                                       Upload Learning Resources
                                   </h5>
                                   <p class="ml-8">Share learning materials, documents, and resources with your students to support their learning.</p>
                               </div>
                           </div>
                       </div>
                       <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-between">
                           <button
                               @click="open = false"
                               wire:click="markOnboardingStepComplete(1)" {{-- Mark step 1 as seen --}}
                               class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                           >
                               I'll do this later
                           </button>
                           <a
                               href="{{ $studentResourceUrl }}"
                               {{-- Also mark step 1 as seen when clicking the primary action --}}
                               wire:click="markOnboardingStepComplete(1)"
                               @click="open = false" {{-- Close modal immediately on click, Livewire handles backend --}}
                               class="px-4 py-2 bg-primary-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                           >
                               Add Students Now
                           </a>
                       </div>

                   {{-- State 2: Create Activities --}}
                   @elseif($onboardingState === 2)
                       <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                           <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                               Great Progress! What's Next?
                           </h3>
                            {{-- Close button also marks step 2 as seen --}}
                           <button @click="open = false" wire:click="markOnboardingStepComplete(2)" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                               <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                           </button>
                       </div>
                       <div class="px-6 py-4">
                            <div class="flex items-center justify-center mb-6">
                               <div class="w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                               </div>
                           </div>
                           <h4 class="text-lg font-medium text-gray-900 dark:text-white text-center mb-4">Time to Create Learning Activities</h4>
                            <div class="text-sm text-gray-500 dark:text-gray-400 space-y-4">
                               <p>Excellent! You've added {{ $studentThreshold }} or more students to your class. Now you can start engaging them with learning activities.</p>
                               <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 opacity-60"> {{-- Step 1 is done --}}
                                   <h5 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                       <span class="flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white mr-2 text-xs">✓</span> {{-- Checkmark for done --}}
                                       Add Your Students
                                   </h5>
                                   <p class="ml-8">You've started building your class roster.</p>
                               </div>
                                <div class="bg-primary-50 dark:bg-primary-700/50 rounded-lg p-4 border border-primary-200 dark:border-primary-700"> {{-- Highlight Step 2 --}}
                                   <h5 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                       <span class="flex items-center justify-center w-6 h-6 rounded-full bg-primary-500 text-white mr-2 text-xs">2</span>
                                       Create Learning Activities
                                   </h5>
                                   <p class="ml-8">Design assignments, quizzes, discussions, or other tasks for your students to complete.</p>
                               </div>
                               <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700 opacity-60"> {{-- De-emphasize Step 3 --}}
                                   <h5 class="font-medium text-gray-900 dark:text-white mb-2 flex items-center">
                                       <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-500 text-white mr-2 text-xs">3</span>
                                       Upload Learning Resources
                                   </h5>
                                   <p class="ml-8">Later, you can also upload documents or links relevant to your class.</p>
                               </div>
                           </div>
                       </div>
                       <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 flex justify-between">
                           <button
                               @click="open = false"
                               wire:click="markOnboardingStepComplete(2)" {{-- Mark step 2 as seen --}}
                               class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                           >
                               I'll do this later
                           </button>
                           <a
                               href="{{ $activityResourceCreateUrl }}"
                               {{-- Also mark step 2 as seen when clicking the primary action --}}
                               wire:click="markOnboardingStepComplete(2)"
                               @click="open = false" {{-- Close modal immediately on click, Livewire handles backend --}}
                               class="px-4 py-2 bg-primary-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                           >
                               Create Activity Now
                           </a>
                       </div>
                   @endif {{-- End onboardingState check --}}
               </div>
           </div>
           @endif {{-- End @if($onboardingState > 0) --}}

</x-filament-panels::page>
