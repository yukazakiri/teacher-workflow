<div 
    x-data="{ 
        currentStep: $wire.entangle('currentStep').live,
        previewMode: $wire.entangle('previewMode').live,
        showHelpTips: $wire.entangle('showHelpTips').live,
        currentPanel: $wire.entangle('currentPanel').live,
        dragActive: false,
        openModal: null,
        init() {
            this.$watch('previewMode', value => {
                if (value) {
                    this.currentPanel = 'preview';
                } else {
                    this.currentPanel = 'edit';
                }
            });
        }
    }" 
    class="relative"
    :class="[$wire.selectedTheme === 'default' ? '' : $wire.colorThemes[$wire.selectedTheme].bg]"
>
    {{-- Header Controls --}}
    <div class="sticky top-0 z-10 mb-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="flex justify-between items-center p-3">
            <div class="flex items-center space-x-3">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                    <span x-show="currentStep === 1">Choose Activity Template</span>
                    <span x-show="currentStep > 1 && !previewMode">
                        Creating: {{ $title ?: 'New Activity' }}
                    </span>
                    <span x-show="previewMode">
                        Preview: {{ $title ?: 'New Activity' }}
                    </span>
                </h1>
                <span x-show="currentStep > 1" class="px-2 py-1 text-xs font-medium rounded-full" :class="$wire.status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'">
                    {{ ucfirst($status) }}
                </span>
            </div>
            
            <div class="flex items-center space-x-2">
                {{-- Color Theme Selector --}}
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <x-heroicon-o-swatch class="w-5 h-5" />
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-20">
                        <div class="p-2">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Color Theme</p>
                            <div class="grid grid-cols-5 gap-1">
                                @foreach($colorThemes as $themeName => $themeColors)
                                    <button type="button" wire:click="setTheme('{{ $themeName }}')" class="w-8 h-8 rounded-full border-2" :class="$wire.selectedTheme === '{{ $themeName }}' ? 'border-black dark:border-white' : 'border-transparent'">
                                        <div class="w-full h-full rounded-full" :class="'{{ $themeName }}' === 'default' ? 'bg-white dark:bg-gray-700' : 'bg-{{ $themeColors['accent'] }}-500'"></div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview Toggle --}}
                <button type="button" x-show="currentStep > 1" @click="$wire.togglePreviewMode()" class="flex items-center px-3 py-1.5 text-sm font-medium rounded-md" :class="previewMode ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'">
                    <x-heroicon-o-eye x-show="!previewMode" class="w-4 h-4 mr-1" />
                    <x-heroicon-o-pencil x-show="previewMode" class="w-4 h-4 mr-1" />
                    <span x-text="previewMode ? 'Edit' : 'Preview'"></span>
                </button>

                {{-- Help Toggle --}}
                <button type="button" @click="$wire.toggleHelpTips()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" :class="{'bg-amber-100 dark:bg-amber-900 rounded-full': showHelpTips}">
                    <x-heroicon-o-question-mark-circle class="w-5 h-5" />
                </button>
            </div>
        </div>

        {{-- Step Indicator --}}
        <nav aria-label="Progress" x-show="currentStep > 1 || previewMode" class="border-t border-gray-200 dark:border-gray-700">
            <ol role="list" class="md:flex">
                @php
                    $steps = [
                        1 => ['name' => 'Template', 'description' => 'Choose a starting point', 'icon' => 'heroicon-o-document-duplicate'],
                        2 => ['name' => 'Details', 'description' => 'Title & content', 'icon' => 'heroicon-o-information-circle'],
                        3 => ['name' => 'Configuration', 'description' => 'Setup options', 'icon' => 'heroicon-o-cog'],
                        4 => ['name' => 'Submission', 'description' => 'Student work', 'icon' => 'heroicon-o-arrow-down-tray'],
                        5 => ['name' => 'Review', 'description' => 'Confirm & create', 'icon' => 'heroicon-o-check-circle'],
                    ];
                @endphp

                @foreach ($steps as $stepNumber => $stepInfo)
                    <li class="relative md:flex-1 md:flex {{ !$loop->last ? 'md:border-r md:border-gray-200 dark:md:border-gray-700' : '' }}">
                        {{-- Step Button --}}
                        <button type="button" 
                            wire:click="goToStep({{ $stepNumber }})" 
                            class="group w-full flex items-center"
                            :class="{ 
                                'hover:bg-gray-50 dark:hover:bg-gray-800': currentStep !== {{ $stepNumber }},
                                'bg-gray-50 dark:bg-gray-800': currentStep === {{ $stepNumber }}
                            }"
                        >
                            <span class="px-4 py-2 flex items-center text-sm font-medium">
                                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full"
                                    :class="{
                                        'bg-primary-600 group-hover:bg-primary-800': $wire.stepProgress[{{ $stepNumber }}],
                                        'border-2 border-gray-300 dark:border-gray-600 group-hover:border-gray-400': !$wire.stepProgress[{{ $stepNumber }}],
                                        'border-2 border-primary-500': currentStep === {{ $stepNumber }} && !$wire.stepProgress[{{ $stepNumber }}]
                                    }"
                                >
                                    <template x-if="$wire.stepProgress[{{ $stepNumber }}]">
                                        <x-heroicon-s-check class="w-5 h-5 text-white" />
                                    </template>
                                    <template x-if="!$wire.stepProgress[{{ $stepNumber }}]">
                                        <x-dynamic-component :component="$stepInfo['icon']" class="w-5 h-5" :class="{'text-primary-600': currentStep === {{ $stepNumber }}, 'text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white': currentStep !== {{ $stepNumber }}}" />
                                    </template>
                                </span>
                                <span class="ml-3 text-sm font-medium"
                                    :class="{
                                        'text-primary-600': currentStep === {{ $stepNumber }},
                                        'text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white': currentStep !== {{ $stepNumber }}
                                    }"
                                >
                                    {{ $stepInfo['name'] }}
                                </span>
                            </span>
                        </button>
                        
                        @if (!$loop->last)
                            <!-- Arrow separator -->
                            <div class="hidden md:block absolute top-0 right-0 h-full w-5" aria-hidden="true">
                                <svg class="h-full w-full text-gray-300 dark:text-gray-600" viewBox="0 0 22 80" fill="none" preserveAspectRatio="none">
                                    <path d="M0 -2L20 40L0 82" vector-effect="non-scaling-stroke" stroke="currentcolor" stroke-linejoin="round" />
                                </svg>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>

    {{-- Main Content Area --}}
    <div class="relative space-y-6">
        {{-- Step 1: Template Selection --}}
        <div x-show="currentStep === 1" class="space-y-6 sm:px-0">
            <div class="max-w-3xl mx-auto">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Create a New Activity</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Choose a template to get started or build from scratch.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($templatesData as $key => $template)
                    <div class="group relative flex flex-col rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm transition-all duration-150 hover:shadow-md focus-within:ring-2 focus-within:ring-primary-500 dark:focus-within:ring-primary-600 overflow-hidden">
                        <!-- Template Preview Image -->
                        <div class="aspect-w-16 aspect-h-9 bg-gray-100 dark:bg-gray-900">
                            @if(isset($template['thumbnail']))
                                <img src="{{ asset('images/templates/' . $template['thumbnail']) }}" alt="{{ $template['name'] }} preview" class="object-cover object-center group-hover:opacity-90">
                            @else
                                <div class="flex items-center justify-center h-full">
                                    <x-dynamic-component :component="$template['icon'] ?? 'heroicon-o-document-text'" class="h-12 w-12 text-gray-400" />
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-start p-4">
                                <button type="button" wire:click="selectTemplate('{{ $key }}')" 
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-white/90 text-gray-900 hover:bg-white transition-colors">
                                    <x-heroicon-o-arrow-right class="mr-1 h-3 w-3" />
                                    Select
                                </button>
                            </div>
                        </div>
                        
                        <!-- Template Info -->
                        <div class="flex-1 flex flex-col p-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center p-2 rounded-md bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400">
                                        <x-dynamic-component :component="$template['icon'] ?? 'heroicon-o-document-text'" class="h-5 w-5" />
                                    </span>
                                    <h3 class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ $template['name'] }}</h3>
                                </div>
                                
                                <!-- Preview Button -->
                                <button type="button" class="ml-2 p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-300" x-data="{}" x-tooltip.raw="{{ $template['description'] }}">
                                    <x-heroicon-o-information-circle class="h-4 w-4" />
                                </button>
                            </div>
                            
                            <p class="mt-2 flex-grow text-xs text-gray-500 dark:text-gray-400">{{ $template['description'] }}</p>
                            
                            <button type="button" wire:click="selectTemplate('{{ $key }}')" class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800">
                                Use This Template
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Quick Start Guide -->
            <div class="mt-12 max-w-3xl mx-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6" x-show="showHelpTips">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-light-bulb class="h-6 w-6 text-amber-500" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Quick Start Guide</h3>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Choose a template that best fits your activity type.</li>
                                <li>Templates provide pre-configured settings you can customize.</li>
                                <li>The "Start from Scratch" option allows full customization.</li>
                                <li>You can preview your activity at any point using the Preview button.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="ml-auto">
                        <button type="button" @click="showHelpTips = false" class="text-gray-400 hover:text-gray-500">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Step 2: Details --}}
        <div x-show="currentStep === 2" x-transition.opacity class="space-y-6">
            <div x-show="previewMode" class="relative rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="absolute top-4 right-4 px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 rounded">Preview Mode</div>
                
                <div class="max-w-3xl mx-auto pt-8">
                    <!-- Activity Preview -->
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $title ?: 'Activity Title' }}</h1>
                    
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($activityTypeId)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $activityTypeOptions[$activityTypeId] ?? 'Type' }}
                            </span>
                        @endif
                        
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ $totalPoints }} points
                        </span>
                        
                        @if($deadline)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                Due: {{ \Carbon\Carbon::parse($deadline)->format('M d, Y') }}
                            </span>
                        @endif
                    </div>
                    
                    @if($description)
                        <div class="mt-6 prose prose-sm dark:prose-invert max-w-none">
                            {!! $description !!}
                        </div>
                    @endif
                    
                    @if($instructions)
                        <div class="mt-8">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Instructions</h2>
                            <div class="mt-2 prose prose-sm dark:prose-invert max-w-none">
                                {!! $instructions !!}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <div x-show="!previewMode">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center">
                            <span>Basic Information</span>
                            @if($showHelpTips)
                                <span class="ml-2 text-xs px-2 py-0.5 bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 rounded-full">Step 1 of 4</span>
                            @endif
                        </div>
                    </x-slot>
                    
                    <x-filament-forms::field-wrapper id="title-wrapper" label="Title" required :error="$errors->first('title')">
                        <x-filament-forms::text-input type="text" wire:model.blur="title" id="title" placeholder="e.g., Chapter 1 Quiz, Photosynthesis Research Paper" :error="$errors->has('title')"/>
                        @if($showHelpTips)
                            <div class="mt-1 text-xs text-primary-600 dark:text-primary-400">
                                <x-heroicon-o-light-bulb class="inline-block w-3 h-3 mr-1" /> 
                                Choose a clear, descriptive title that students will recognize.
                            </div>
                        @endif
                    </x-filament-forms::field-wrapper>

                     <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <x-filament-forms::field-wrapper id="activityTypeId-wrapper" label="Activity Type" required :error="$errors->first('activityTypeId')">
                            <x-filament-forms::select wire:model="activityTypeId" id="activityTypeId" :error="$errors->has('activityTypeId')">
                                <option value="">Select type...</option>
                                @foreach($activityTypeOptions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </x-filament-forms::select>
                             <x-slot name="helper-text">
                                Categorize the activity (e.g., Homework, Quiz, Lab).
                            </x-slot>
                        </x-filament-forms::field-wrapper>

                        <x-filament-forms::field-wrapper id="status-wrapper" label="Status" required :error="$errors->first('status')">
                            <x-filament-forms::select wire:model="status" id="status" :error="$errors->has('status')">
                                <option value="draft">Draft (Hidden from students)</option>
                                <option value="published">Published (Visible to students)</option>
                            </x-filament-forms::select>
                        </x-filament-forms::field-wrapper>

                        <x-filament-forms::field-wrapper id="deadline-wrapper" label="Submission Deadline (Optional)" :error="$errors->first('deadline')">
                             <x-filament-forms::date-time-picker wire:model.blur="deadline" id="deadline" placeholder="Set a deadline" :error="$errors->has('deadline')" />
                              <x-slot name="helper-text">
                               Students can still submit after the deadline if allowed in submission settings.
                            </x-slot>
                        </x-filament-forms::field-wrapper>
                    </div>
                </x-filament::section>

                 <x-filament::section class="mt-6">
                    <x-slot name="heading">Content</x-slot>
                     <x-filament-forms::field-wrapper id="description-wrapper" label="Description (Optional)" :error="$errors->first('description')">
                         <x-filament-forms::rich-editor
                            wire:model.lazy="description"
                            id="description"
                            placeholder="Provide a general overview or context for the activity."
                            :error="$errors->has('description')"
                        />
                         <x-slot name="helper-text">This appears on the activity list and at the top of the activity page.</x-slot>
                     </x-filament-forms::field-wrapper>

                      <x-filament-forms::field-wrapper id="instructions-wrapper" label="Instructions (Optional)" :error="$errors->first('instructions')">
                         <x-filament-forms::rich-editor
                            wire:model.lazy="instructions"
                            id="instructions"
                            placeholder="Provide detailed steps, guidance, or resources needed."
                            :error="$errors->has('instructions')"
                        />
                          <x-slot name="helper-text">Clear instructions help students understand expectations.</x-slot>
                     </x-filament-forms::field-wrapper>
                 </x-filament::section>
            </div>
        </div>

        {{-- Step 3: Configuration --}}
        <div x-show="currentStep === 3" x-transition.opacity class="space-y-6">
            <div x-show="previewMode" class="relative rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="absolute top-4 right-4 px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 rounded">Preview Mode</div>
                
                <div class="max-w-3xl mx-auto pt-8">
                    <!-- Config Preview -->
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Activity Configuration</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 p-3">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mode</h3>
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400">
                                        <x-heroicon-o-user-group class="h-4 w-4" x-show="$wire.mode === 'group'" />
                                        <x-heroicon-o-user class="h-4 w-4" x-show="$wire.mode === 'individual'" />
                                        <x-heroicon-o-home class="h-4 w-4" x-show="$wire.mode === 'take_home'" />
                                    </span>
                                    <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">
                                        <span x-show="$wire.mode === 'individual'">Individual Activity</span>
                                        <span x-show="$wire.mode === 'group'">Group Activity</span>
                                        <span x-show="$wire.mode === 'take_home'">Take-Home Activity</span>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 p-3">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Grading</h3>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $totalPoints }} Points</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $category === 'written' ? 'Written Work' : 'Performance Task' }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 p-3">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Format</h3>
                                <span class="text-sm text-gray-900 dark:text-white">
                                    {{ $format === 'other' ? $customFormat : ucfirst($format ?: 'Not set') }}
                                </span>
                            </div>
                            
                            @if($mode === 'group')
                                <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 p-3">
                                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Group Setup</h3>
                                    <div class="flex flex-col">
                                        <span class="text-sm text-gray-900 dark:text-white">
                                            {{ $groupCount ? $groupCount . ' Groups' : 'Groups Not Specified' }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ count($roles) }} Role(s) Defined
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        @if($mode === 'group' && count($roles) > 0)
                            <div class="mt-6">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Group Roles</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($roles as $role)
                                        <div class="bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700 p-3">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $role['name'] }}</h4>
                                            @if(!empty($role['description']))
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $role['description'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
                
            <div x-show="!previewMode">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center">
                            <span>Activity Setup</span>
                            @if($showHelpTips)
                                <span class="ml-2 text-xs px-2 py-0.5 bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 rounded-full">Step 2 of 4</span>
                            @endif
                        </div>
                    </x-slot>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <x-filament-forms::field-wrapper id="mode-wrapper" label="Activity Mode" required :error="$errors->first('mode')">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div wire:click="$set('mode', 'individual')" class="cursor-pointer rounded-lg border-2 p-4 transition-colors" 
                                        :class="$wire.mode === 'individual' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                        <div class="flex justify-center mb-2">
                                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400">
                                                <x-heroicon-o-user class="h-6 w-6" />
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-medium text-center text-gray-900 dark:text-white">Individual Activity</h3>
                                        <p class="mt-1 text-xs text-center text-gray-500 dark:text-gray-400">Students complete work independently</p>
                                    </div>
                                    
                                    <div wire:click="$set('mode', 'group')" class="cursor-pointer rounded-lg border-2 p-4 transition-colors" 
                                        :class="$wire.mode === 'group' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                        <div class="flex justify-center mb-2">
                                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400">
                                                <x-heroicon-o-user-group class="h-6 w-6" />
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-medium text-center text-gray-900 dark:text-white">Group Activity</h3>
                                        <p class="mt-1 text-xs text-center text-gray-500 dark:text-gray-400">Students collaborate in teams</p>
                                    </div>
                                    
                                    <div wire:click="$set('mode', 'take_home')" class="cursor-pointer rounded-lg border-2 p-4 transition-colors" 
                                        :class="$wire.mode === 'take_home' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                        <div class="flex justify-center mb-2">
                                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400">
                                                <x-heroicon-o-home class="h-6 w-6" />
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-medium text-center text-gray-900 dark:text-white">Take-Home Activity</h3>
                                        <p class="mt-1 text-xs text-center text-gray-500 dark:text-gray-400">Students work outside of class</p>
                                    </div>
                                </div>
                                
                                <x-slot name="helper-text">
                                   Choose if students work alone, in groups, or on their own time.
                                </x-slot>
                            </x-filament-forms::field-wrapper>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <x-filament-forms::field-wrapper id="category-wrapper" label="Grading Category" required :error="$errors->first('category')">
                                <div class="grid grid-cols-2 gap-3">
                                    <div wire:click="$set('category', 'written')" class="cursor-pointer rounded-lg border-2 p-3 transition-colors" 
                                        :class="$wire.category === 'written' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                        <div class="flex justify-center mb-2">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400">
                                                <x-heroicon-o-document-text class="h-4 w-4" />
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-medium text-center text-gray-900 dark:text-white">Written Work</h3>
                                    </div>
                                    
                                    <div wire:click="$set('category', 'performance')" class="cursor-pointer rounded-lg border-2 p-3 transition-colors" 
                                        :class="$wire.category === 'performance' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                        <div class="flex justify-center mb-2">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400">
                                                <x-heroicon-o-presentation-chart-bar class="h-4 w-4" />
                                            </span>
                                        </div>
                                        <h3 class="text-sm font-medium text-center text-gray-900 dark:text-white">Performance Task</h3>
                                    </div>
                                </div>
                                
                                <x-slot name="helper-text">
                                   Aligns with grading components (important for report cards/transcripts).
                                </x-slot>
                            </x-filament-forms::field-wrapper>

                            <x-filament-forms::field-wrapper id="totalPoints-wrapper" label="Total Points" required :error="$errors->first('totalPoints')">
                                <div class="flex items-center">
                                    <x-filament-forms::text-input type="number" wire:model.blur="totalPoints" id="totalPoints" min="0" step="0.5" :error="$errors->has('totalPoints')" class="flex-grow"/>
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">points</span>
                                </div>
                                
                                <div class="mt-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Quick Set:</div>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach([5, 10, 20, 50, 100] as $pointValue)
                                            <button type="button" wire:click="$set('totalPoints', {{ $pointValue }})" 
                                                class="px-2 py-0.5 text-xs rounded-md transition-colors"
                                                :class="$wire.totalPoints == {{ $pointValue }} ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
                                                {{ $pointValue }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                
                                <x-slot name="helper-text">
                                   The maximum score achievable for this activity.
                                </x-slot>
                            </x-filament-forms::field-wrapper>
                        </div>
                        
                        <x-filament-forms::field-wrapper id="format-wrapper" label="Activity Format" required :error="$errors->first('format')">
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                                @php
                                    $formatIcons = [
                                        'quiz' => 'heroicon-o-clipboard-document-check',
                                        'assignment' => 'heroicon-o-document-text',
                                        'reporting' => 'heroicon-o-presentation-chart-bar',
                                        'presentation' => 'heroicon-o-rectangle-group',
                                        'discussion' => 'heroicon-o-chat-bubble-left-right',
                                        'project' => 'heroicon-o-cube-transparent',
                                        'other' => 'heroicon-o-adjustments-horizontal',
                                    ];
                                @endphp
                                
                                @foreach(['quiz', 'assignment', 'reporting', 'presentation', 'discussion', 'project', 'other'] as $formatOption)
                                    <div wire:click="$set('format', '{{ $formatOption }}')" class="cursor-pointer rounded-lg border-2 p-2 transition-colors" 
                                        :class="$wire.format === '{{ $formatOption }}' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                        <div class="flex flex-col items-center">
                                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400">
                                                <x-dynamic-component :component="$formatIcons[$formatOption] ?? 'heroicon-o-document'" class="h-4 w-4" />
                                            </span>
                                            <h3 class="mt-1 text-xs font-medium text-center text-gray-900 dark:text-white">{{ ucfirst($formatOption) }}</h3>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div x-show="$wire.format === 'other'" class="mt-3">
                                <x-filament-forms::text-input type="text" wire:model.blur="customFormat" id="customFormat" placeholder="Specify custom format" :error="$errors->has('customFormat')"/>
                            </div>
                         </x-filament-forms::field-wrapper>
                    </div>
                 </x-filament::section>

                 {{-- Group Settings --}}
                <div x-show="$wire.mode === 'group'" x-transition class="space-y-6 mt-6">
                     <x-filament::section >
                        <x-slot name="heading">Group Settings</x-slot>
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <x-filament-forms::field-wrapper id="groupCount-wrapper" label="Approx. Number of Groups (Optional)" :error="$errors->first('groupCount')">
                                <x-filament-forms::text-input type="number" wire:model.blur="groupCount" id="groupCount" min="2" :error="$errors->has('groupCount')"/>
                                <x-slot name="helper-text">
                                    For planning or auto-assignment (if feature exists). Groups are managed separately after creation.
                                </x-slot>
                            </x-filament-forms::field-wrapper>
                        </div>

                        {{-- Roles Builder --}}
                        <div class="mt-6">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center">
                                Group Roles
                                <span class="ml-2 text-xs px-2 py-0.5 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-full">Optional</span>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Define specific roles for group members to encourage collaboration.</p>
                            
                            <div class="space-y-4">
                                @forelse ($roles as $index => $role)
                                    <div wire:key="role-{{ $index }}" class="flex items-start gap-4 p-4 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 shadow-sm">
                                        <div class="flex-shrink-0 mt-1">
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400">
                                                <span class="text-sm font-medium">{{ $index + 1 }}</span>
                                            </span>
                                        </div>
                                        <div class="flex-1 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <x-filament-forms::field-wrapper label="Role Name" required :error="$errors->first('roles.'. $index . '.name')">
                                                <x-filament-forms::text-input type="text" wire:model.blur="roles.{{ $index }}.name" id="role_name_{{ $index }}" placeholder="e.g., Leader, Recorder" :error="$errors->has('roles.'. $index . '.name')"/>
                                            </x-filament-forms::field-wrapper>
                                            <x-filament-forms::field-wrapper label="Role Description" :error="$errors->first('roles.'. $index . '.description')">
                                                 <x-filament-forms::textarea wire:model.blur="roles.{{ $index }}.description" id="role_description_{{ $index }}" rows="2" placeholder="Describe responsibilities" :error="$errors->has('roles.'. $index . '.description')"/>
                                            </x-filament-forms::field-wrapper>
                                        </div>
                                        <div class="flex-shrink-0">
                                             <x-filament::button type="button" color="danger" size="sm" outlined wire:click="removeRole({{ $index }})" title="Remove Role">
                                                <x-heroicon-o-trash class="w-4 h-4"/>
                                             </x-filament::button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-6 border border-dashed border-gray-300 dark:border-gray-700 rounded-md text-center bg-gray-50 dark:bg-gray-800">
                                        <div class="flex justify-center mb-2">
                                            <x-heroicon-o-user-group class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">No roles defined yet.</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Click "Add Role" to define group member responsibilities.</p>
                                    </div>
                                @endforelse
                            </div>
                            <div class="mt-4 flex justify-center">
                                <x-filament::button type="button" outlined size="sm" wire:click="addRole" class="gap-1">
                                    <x-heroicon-o-plus class="w-4 h-4" />
                                    Add Role
                                </x-filament::button>
                            </div>
                        </div>
                    </x-filament::section>
                </div>
            </div>
        </div>

        {{-- Step 4: Submission --}}
        <div x-show="currentStep === 4" x-transition.opacity class="space-y-6">
            <div x-show="previewMode" class="relative rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                <div class="absolute top-4 right-4 px-2 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 rounded">Preview Mode</div>
                
                <div class="max-w-3xl mx-auto pt-8">
                    <!-- Submission Preview -->
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Submission Requirements</h2>
                    
                    <div class="mt-4 bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <div class="flex items-center">
                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400">
                                <x-heroicon-o-document-arrow-down class="h-6 w-6" x-show="$wire.submissionType === 'resource'" />
                                <x-heroicon-o-clipboard-document-list class="h-6 w-6" x-show="$wire.submissionType === 'form'" />
                                <x-heroicon-o-clipboard class="h-6 w-6" x-show="$wire.submissionType === 'manual'" />
                            </span>
                            <div class="ml-3">
                                <h3 class="text-base font-medium text-gray-900 dark:text-white">
                                    <span x-show="$wire.submissionType === 'resource'">File Upload / Text Entry</span>
                                    <span x-show="$wire.submissionType === 'form'">Online Form</span>
                                    <span x-show="$wire.submissionType === 'manual'">Manual Grading Only</span>
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <span x-show="$wire.submissionType === 'resource'">
                                        Students will submit files and/or enter text directly.
                                    </span>
                                    <span x-show="$wire.submissionType === 'form'">
                                        Students will complete a structured form with {{ count($formStructure) }} question(s).
                                    </span>
                                    <span x-show="$wire.submissionType === 'manual'">
                                        No online submission required. Teacher will grade manually.
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    @if($submissionType === 'resource')
                        <div class="mt-6 space-y-4">
                            @if($allowFileUploads)
                                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center">
                                        <x-heroicon-o-paper-clip class="h-4 w-4 mr-1" />
                                        File Uploads
                                    </h3>
                                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <p>Max file size: {{ $maxFileSize }} MB</p>
                                        @if(count($allowedFileTypes) > 0)
                                            <div class="mt-2">
                                                <p class="mb-1">Allowed file types:</p>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($allowedFileTypes as $type)
                                                        <span class="inline-block bg-gray-100 dark:bg-gray-700 rounded px-2 py-0.5 text-xs">
                                                            {{ $commonFileTypeOptions[$type] ?? $type }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <p class="mt-1">All file types allowed</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            
                            @if($allowTextEntry)
                                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white flex items-center">
                                        <x-heroicon-o-document-text class="h-4 w-4 mr-1" />
                                        Text Entry
                                    </h3>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        Students can type their submission directly into a text editor.
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif
                    
                    @if($submissionType === 'form')
                        <div class="mt-6">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Form Questions</h3>
                            <div class="space-y-4">
                                @forelse($formStructure as $index => $question)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                        <div class="flex items-start">
                                            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 flex-shrink-0">
                                                {{ $index + 1 }}
                                            </span>
                                            <div class="ml-3 flex-grow">
                                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $question['question'] ?? 'Question ' . ($index + 1) }}
                                                </h4>
                                                <div class="mt-2">
                                                    @if($question['type'] === 'multiple_choice')
                                                        <div class="space-y-1">
                                                            @foreach($question['options'] ?? [] as $option)
                                                                <div class="flex items-center">
                                                                    <div class="h-4 w-4 rounded-full border border-gray-300 dark:border-gray-600"></div>
                                                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $option }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @elseif($question['type'] === 'short_answer')
                                                        <div class="h-8 bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600"></div>
                                                    @elseif($question['type'] === 'essay')
                                                        <div class="h-24 bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600"></div>
                                                    @elseif($question['type'] === 'file_upload')
                                                        <div class="flex items-center justify-center h-16 bg-gray-100 dark:bg-gray-700 rounded border border-dashed border-gray-300 dark:border-gray-600">
                                                            <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                                                <x-heroicon-o-paper-clip class="h-3 w-3 mr-1" />
                                                                Upload File
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <x-heroicon-o-clipboard-document-list class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-3" />
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No questions yet</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Click "Add Question" to start building your form.</p>
                                            <x-filament::button type="button" size="sm" wire:click="$dispatch('open-modal', { id: 'add-question-modal' })" class="gap-1">
                                                <x-heroicon-o-plus class="w-4 h-4" />
                                                Add Your First Question
                                            </x-filament::button>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <div x-show="!previewMode">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center">
                            <span>Submission Type</span>
                            @if($showHelpTips)
                                <span class="ml-2 text-xs px-2 py-0.5 bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300 rounded-full">Step 3 of 4</span>
                            @endif
                        </div>
                    </x-slot>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div wire:click="$set('submissionType', 'resource')" 
                            class="cursor-pointer rounded-lg border-2 transition-colors p-4 flex flex-col h-full"
                            :class="$wire.submissionType === 'resource' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                            <div class="flex justify-center mb-3">
                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400">
                                    <x-heroicon-o-document-arrow-down class="h-6 w-6" />
                                </span>
                            </div>
                            <h3 class="text-sm font-medium text-center text-gray-900 dark:text-white">File Upload / Text Entry</h3>
                            <p class="mt-1 text-xs text-center text-gray-500 dark:text-gray-400">
                                Students upload files or type text directly
                            </p>
                        </div>
                        
                        <div wire:click="$set('submissionType', 'form')" 
                            class="cursor-pointer rounded-lg border-2 transition-colors p-4 flex flex-col h-full"
                            :class="$wire.submissionType === 'form' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                            <div class="flex justify-center mb-3">
                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400">
                                    <x-heroicon-o-clipboard-document-list class="h-6 w-6" />
                                </span>
                            </div>
                            <h3 class="text-sm font-medium text-center text-gray-900 dark:text-white">Online Form</h3>
                            <p class="mt-1 text-xs text-center text-gray-500 dark:text-gray-400">
                                Create structured questions for students to answer
                            </p>
                        </div>
                        
                        <div wire:click="$set('submissionType', 'manual')" 
                            class="cursor-pointer rounded-lg border-2 transition-colors p-4 flex flex-col h-full"
                            :class="$wire.submissionType === 'manual' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                            <div class="flex justify-center mb-3">
                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900 text-amber-600 dark:text-amber-400">
                                    <x-heroicon-o-clipboard class="h-6 w-6" />
                                </span>
                            </div>
                            <h3 class="text-sm font-medium text-center text-gray-900 dark:text-white">Manual Grading Only</h3>
                            <p class="mt-1 text-xs text-center text-gray-500 dark:text-gray-400">
                                No online submission needed (in-class work, presentations)
                            </p>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Resource Submission Options --}}
                <div x-show="$wire.submissionType === 'resource'" x-transition class="space-y-6 mt-6">
                    <x-filament::section>
                        <x-slot name="heading">File Upload / Text Entry Options</x-slot>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">File Uploads</h3>
                                    <x-filament-forms::toggle wire:model.live="allowFileUploads" id="allowFileUploads" :error="$errors->has('allowFileUploads')"/>
                                </div>
                                
                                <div x-show="$wire.allowFileUploads" x-collapse>
                                    <div class="space-y-4">
                                        <x-filament-forms::field-wrapper id="maxFileSize-wrapper" label="Maximum File Size (MB)" :error="$errors->first('maxFileSize')">
                                            <div class="flex items-center">
                                                <input type="range" wire:model.live="maxFileSize" min="1" max="100" class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer">
                                                <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">{{ $maxFileSize }} MB</span>
                                            </div>
                                        </x-filament-forms::field-wrapper>
                                        
                                        <x-filament-forms::field-wrapper id="allowedFileTypes-wrapper" label="Allowed File Types" :error="$errors->first('allowedFileTypes')">
                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach($commonFileTypeOptions as $type => $label)
                                                    <label class="flex items-center space-x-2 cursor-pointer p-2 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                                        <input type="checkbox" wire:model.live="allowedFileTypes" value="{{ $type }}" class="rounded text-primary-600 focus:ring-primary-500 dark:bg-gray-700">
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <x-slot name="helper-text">Leave empty to allow all file types (use with caution)</x-slot>
                                        </x-filament-forms::field-wrapper>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">Text Entry</h3>
                                    <x-filament-forms::toggle wire:model.live="allowTextEntry" id="allowTextEntry" :error="$errors->has('allowTextEntry')"/>
                                </div>
                                
                                <div x-show="$wire.allowTextEntry" x-collapse>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Students can type their submission directly into a rich text editor.
                                    </p>
                                    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-md border border-dashed border-gray-300 dark:border-gray-700">
                                        <div class="flex items-center space-x-1 text-gray-500 dark:text-gray-400 text-xs mb-1">
                                            <span class="font-bold">B</span>
                                            <span class="italic">I</span>
                                            <span class="underline">U</span>
                                            <span>¶</span>
                                        </div>
                                        <div class="h-16 bg-white dark:bg-gray-800 rounded border border-gray-300 dark:border-gray-600"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if($errors->has('allowFileUploads') && Str::contains($errors->first('allowFileUploads'), 'at least one'))
                            <div class="mt-2 text-sm text-danger-600 dark:text-danger-400">
                                <x-heroicon-o-exclamation-triangle class="inline-block w-4 h-4 mr-1" />
                                {{ $errors->first('allowFileUploads') }}
                            </div>
                        @endif
                    </x-filament::section>
                </div>

                {{-- Form Submission Options --}}
                <div x-show="$wire.submissionType === 'form'" x-transition class="space-y-6 mt-6">
                    <x-filament::section>
                        <x-slot name="heading">Form Builder</x-slot>
                        
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Create structured questions for students to answer.
                            </p>
                            <x-filament::button type="button" size="sm" outlined wire:click="$dispatch('open-modal', { id: 'add-question-modal' })" class="gap-1">
                                <x-heroicon-o-plus class="w-4 h-4" />
                                Add Question
                            </x-filament::button>
                        </div>
                        
                        <div class="space-y-3" 
                            x-data="{ 
                                dragging: null,
                                dragOverIndex: null,
                                startDrag(event, index) {
                                    this.dragging = index;
                                    event.dataTransfer.effectAllowed = 'move';
                                },
                                endDrag() {
                                    this.dragging = null;
                                    this.dragOverIndex = null;
                                },
                                onDrop(event, toIndex) {
                                    $wire.call('moveQuestion', this.dragging, toIndex);
                                    this.endDrag();
                                }
                            }"
                        >
                            @forelse($formStructure as $index => $question)
                                <div 
                                    wire:key="question-{{ $index }}"
                                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-sm relative"
                                    :class="{
                                        'border-dashed border-primary-500 dark:border-primary-400': dragOverIndex === {{ $index }},
                                        'opacity-50': dragging === {{ $index }}
                                    }"
                                    draggable="true"
                                    @dragstart="startDrag($event, {{ $index }})"
                                    @dragend="endDrag"
                                    @dragover.prevent="dragOverIndex = {{ $index }}"
                                    @dragleave.prevent="dragOverIndex === {{ $index }} ? dragOverIndex = null : null"
                                    @drop.prevent="onDrop($event, {{ $index }})"
                                >
                                    {{-- Drag Handle --}}
                                    <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 cursor-move opacity-50 hover:opacity-100">
                                        <x-heroicon-o-bars-3 class="w-5 h-5" />
                                    </div>
                                    
                                    <div class="flex items-start ml-7">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 flex-shrink-0">
                                                    {{ $index + 1 }}
                                                </span>
                                                <h4 class="ml-2 text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $question['question'] ?? 'Question ' . ($index + 1) }}
                                                </h4>
                                                <span class="ml-2 px-2 py-0.5 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                                    @if($question['type'] === 'multiple_choice')
                                                        Multiple Choice
                                                    @elseif($question['type'] === 'short_answer')
                                                        Short Answer
                                                    @elseif($question['type'] === 'essay')
                                                        Essay/Long Answer
                                                    @elseif($question['type'] === 'file_upload')
                                                        File Upload
                                                    @else
                                                        {{ ucfirst(str_replace('_', ' ', $question['type'] ?? 'question')) }}
                                                    @endif
                                                </span>
                                            </div>
                                            
                                            <div class="mt-2 pl-8">
                                                @if($question['type'] === 'multiple_choice')
                                                    <div class="space-y-1">
                                                        @foreach($question['options'] ?? [] as $option)
                                                            <div class="flex items-center">
                                                                <div class="h-4 w-4 rounded-full border border-gray-300 dark:border-gray-600"></div>
                                                                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $option }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif($question['type'] === 'short_answer')
                                                    <div class="h-8 bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600"></div>
                                                @elseif($question['type'] === 'essay')
                                                    <div class="h-24 bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600"></div>
                                                @elseif($question['type'] === 'file_upload')
                                                    <div class="flex items-center justify-center h-16 bg-gray-100 dark:bg-gray-700 rounded border border-dashed border-gray-300 dark:border-gray-600">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                                            <x-heroicon-o-paper-clip class="h-3 w-3 mr-1" />
                                                            Upload File
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="flex space-x-1 ml-2">
                                            <button type="button" class="p-1 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" wire:click="editQuestion({{ $index }})">
                                                <x-heroicon-o-pencil class="w-4 h-4" />
                                            </button>
                                            <button type="button" class="p-1 text-danger-500 hover:text-danger-700 dark:hover:text-danger-300" wire:click="removeVisualElement({{ $index }})">
                                                <x-heroicon-o-trash class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-8 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-clipboard-document-list class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-3" />
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No questions yet</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Click "Add Question" to start building your form.</p>
                                        <x-filament::button type="button" size="sm" wire:click="$dispatch('open-modal', { id: 'add-question-modal' })" class="gap-1">
                                            <x-heroicon-o-plus class="w-4 h-4" />
                                            Add Your First Question
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </x-filament::section>
                </div>

                {{-- Manual Submission Info --}}
                <div x-show="$wire.submissionType === 'manual'" x-transition class="mt-6">
                    <x-filament::section>
                        <x-slot name="heading">Manual Grading</x-slot>
                        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <x-heroicon-o-information-circle class="h-5 w-5 text-amber-500" />
                                </div>
                                <div class="ml-3 text-sm text-amber-800 dark:text-amber-200">
                                    <p>No online submission is required from students for this activity type.</p>
                                    <p class="mt-1">You will manually enter grades or feedback later (e.g., for in-class presentations, participation, or physical assignments).</p>
                                </div>
                            </div>
                        </div>
                    </x-filament::section>
                </div>

                {{-- Common Options for All Submission Types --}}
                <div x-show="$wire.submissionType !== 'manual'" x-transition class="mt-6">
                    <x-filament::section>
                        <x-slot name="heading">Teacher Actions</x-slot>
                        <x-filament-forms::field-wrapper id="allowTeacherSubmission-wrapper" class="flex items-center gap-4" :error="$errors->first('allowTeacherSubmission')">
                            <x-filament-forms::toggle wire:model="allowTeacherSubmission" id="allowTeacherSubmission" :error="$errors->has('allowTeacherSubmission')"/>
                            <label for="allowTeacherSubmission" class="text-sm font-medium">Allow Teacher Submissions</label>
                            <x-slot name="helper-text">Enable this only if teachers need to submit work on behalf of students (e.g., for offline work).</x-slot>
                        </x-filament-forms::field-wrapper>
                    </x-filament::section>
                </div>
            </div>
        </div>

        {{-- Step 5: Review & Create --}}
        <div x-show="currentStep === 5" x-transition.opacity class="space-y-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-500 to-primary-600 p-4">
                        <h2 class="text-xl font-bold text-white">Activity Summary</h2>
                        <p class="text-primary-100 text-sm">Review your activity details before creating</p>
                    </div>
                    
                    <div class="p-6">
                        @if($errors->any())
                            <div class="mb-6 bg-danger-50 dark:bg-danger-900/20 rounded-lg p-4 border border-danger-200 dark:border-danger-800">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-danger-500" />
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-danger-800 dark:text-danger-200">Please fix the following errors:</h3>
                                        <div class="mt-2 text-sm text-danger-700 dark:text-danger-300">
                                            <ul class="list-disc pl-5 space-y-1">
                                                @foreach($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Basic Info Section --}}
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <x-heroicon-o-information-circle class="w-5 h-5 mr-2 text-primary-500" />
                                    Basic Information
                                </h3>
                                <div class="mt-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $title ?: '-' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Activity Type</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $activityTypeOptions[$activityTypeId] ?? '-' }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                            <dd class="mt-1 text-sm">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Deadline</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $deadline ? \Carbon\Carbon::parse($deadline)->format('M d, Y h:i A') : 'Not set' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                            
                            {{-- Content Section --}}
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <x-heroicon-o-document-text class="w-5 h-5 mr-2 text-primary-500" />
                                    Content
                                </h3>
                                <div class="mt-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <dl class="space-y-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                            <dd class="mt-1 prose prose-sm max-w-none dark:prose-invert">
                                                {!! $description ?: '<span class="text-gray-400 dark:text-gray-500 italic">No description provided.</span>' !!}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Instructions</dt>
                                            <dd class="mt-1 prose prose-sm max-w-none dark:prose-invert">
                                                {!! $instructions ?: '<span class="text-gray-400 dark:text-gray-500 italic">No instructions provided.</span>' !!}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                            
                            {{-- Configuration Section --}}
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <x-heroicon-o-cog class="w-5 h-5 mr-2 text-primary-500" />
                                    Configuration
                                </h3>
                                <div class="mt-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mode</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white flex items-center">
                                                <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 mr-1.5">
                                                    <x-heroicon-o-user-group class="h-3 w-3" x-show="$wire.mode === 'group'" />
                                                    <x-heroicon-o-user class="h-3 w-3" x-show="$wire.mode === 'individual'" />
                                                    <x-heroicon-o-home class="h-3 w-3" x-show="$wire.mode === 'take_home'" />
                                                </span>
                                                <span>
                                                    {{ ucfirst(str_replace('_', ' ', $mode)) }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                                {{ $category === 'written' ? 'Written Work' : 'Performance Task' }}
                                            </dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Points</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $totalPoints }}</dd>
                                        </div>
                                        <div class="sm:col-span-1">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Format</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                                {{ $format === 'other' ? $customFormat : ucfirst($format ?: 'Not set') }}
                                            </dd>
                                        </div>
                                        
                                        @if($mode === 'group')
                                            <div class="sm:col-span-3">
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Group Settings</dt>
                                                <dd class="mt-2">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                                            <p class="text-sm text-gray-900 dark:text-white">
                                                                {{ $groupCount ? 'Approximately ' . $groupCount . ' Groups' : 'Number of groups not specified' }}
                                                            </p>
                                                        </div>
                                                        
                                                        @foreach($roles as $role)
                                                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $role['name'] }}</p>
                                                                @if(!empty($role['description']))
                                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $role['description'] }}</p>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                        
                                                        @if(empty($roles))
                                                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No roles defined</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                            
                            {{-- Submission Section --}}
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                    <x-heroicon-o-arrow-down-tray class="w-5 h-5 mr-2 text-primary-500" />
                                    Submission
                                </h3>
                                <div class="mt-3 border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <dl class="space-y-6">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Submission Type</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white flex items-center">
                                                <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 mr-1.5">
                                                    <x-heroicon-o-document-arrow-down class="h-3 w-3" x-show="$wire.submissionType === 'resource'" />
                                                    <x-heroicon-o-clipboard-document-list class="h-3 w-3" x-show="$wire.submissionType === 'form'" />
                                                    <x-heroicon-o-clipboard class="h-3 w-3" x-show="$wire.submissionType === 'manual'" />
                                                </span>
                                                <span>
                                                    <span x-show="$wire.submissionType === 'resource'">File Upload / Text Entry</span>
                                                    <span x-show="$wire.submissionType === 'form'">Online Form</span>
                                                    <span x-show="$wire.submissionType === 'manual'">Manual Grading Only</span>
                                                </span>
                                            </dd>
                                        </div>
                                        
                                        @if($submissionType === 'resource')
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Resource Settings</dt>
                                                <dd class="mt-2">
                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">File Uploads</p>
                                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                                {{ $allowFileUploads ? 'Allowed' : 'Not allowed' }}
                                                                @if($allowFileUploads)
                                                                    (Max: {{ $maxFileSize }} MB)
                                                                @endif
                                                            </p>
                                                            @if($allowFileUploads && count($allowedFileTypes) > 0)
                                                                <div class="mt-2 flex flex-wrap gap-1">
                                                                    @foreach($allowedFileTypes as $type)
                                                                        <span class="inline-block bg-gray-100 dark:bg-gray-700 rounded px-2 py-0.5 text-xs">
                                                                            {{ $commonFileTypeOptions[$type] ?? $type }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                        
                                                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                                            <p class="text-sm font-medium text-gray-900 dark:text-white">Text Entry</p>
                                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                                {{ $allowTextEntry ? 'Allowed' : 'Not allowed' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </dd>
                                            </div>
                                        @endif
                                        
                                        @if($submissionType === 'form')
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Form Questions</dt>
                                                <dd class="mt-2">
                                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                                        <p class="text-sm text-gray-900 dark:text-white">
                                                            {{ count($formStructure) }} question(s) defined
                                                        </p>
                                                        <div class="mt-2 space-y-2">
                                                            @forelse($formStructure as $index => $question)
                                                                <div class="bg-white dark:bg-gray-800 rounded p-2 text-xs text-gray-700 dark:text-gray-300">
                                                                    {{ $index + 1 }}. {{ $question['question'] ?? 'Question ' . ($index + 1) }}
                                                                    <span class="ml-1 text-gray-500 dark:text-gray-400">
                                                                        ({{ ucfirst(str_replace('_', ' ', $question['type'] ?? 'question')) }})
                                                                    </span>
                                                                </div>
                                                            @empty
                                                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">No questions defined</p>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </dd>
                                            </div>
                                        @endif
                                        
                                        @if($submissionType !== 'manual')
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Teacher Actions</dt>
                                                <dd class="mt-2">
                                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                                        <p class="text-sm text-gray-900 dark:text-white">
                                                            Teacher can submit on behalf of students: 
                                                            <span class="font-medium">{{ $allowTeacherSubmission ? 'Yes' : 'No' }}</span>
                                                        </p>
                                                    </div>
                                                </dd>
                                            </div>
                                        @endif
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation Buttons --}}
    <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
        <div>
            <x-filament::button 
                type="button" 
                color="secondary" 
                outlined
                wire:click="previousStep"
                x-show="currentStep > 1"
                class="gap-1"
            >
                <x-heroicon-o-arrow-left class="h-4 w-4" />
                Previous
            </x-filament::button>
        </div>

        <div class="flex items-center space-x-3">
            <div x-show="currentStep >= 2 && !previewMode" class="text-xs text-gray-500 dark:text-gray-400">
                Step {{ $currentStep }} of 5
            </div>
            
             {{-- Show Next button for steps 1-4 --}}
            <x-filament::button 
                type="button"
                wire:click="nextStep"
                x-show="currentStep < 5"
                class="gap-1"
            >
                Next
                <x-heroicon-o-arrow-right class="h-4 w-4" />
            </x-filament::button>

            {{-- Show Create button only on the last step (Review) --}}
            <x-filament::button 
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
                x-show="currentStep === 5"
                class="gap-1"
            >
                <span wire:loading wire:target="save" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating...
                </span>
                <span wire:loading.remove wire:target="save" class="flex items-center">
                    <x-heroicon-o-check class="h-4 w-4 mr-1" />
                    Create Activity
                </span>
            </x-filament::button>
        </div>
    </div>
</div> 