<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Teacher Workflow Analytics
            </x-slot>
            
            <x-slot name="description">
                Comprehensive analytics and insights for your learning management system
            </x-slot>
            
            <div class="space-y-2">
                <p class="text-gray-500 dark:text-gray-400">
                    This dashboard provides detailed statistics on user engagement, learning activities, exams, and resources. 
                    Use these insights to improve educational outcomes and optimize your teaching workflow.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-full bg-primary-100 dark:bg-primary-900 p-3">
                                <x-heroicon-o-academic-cap class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Learning Management</p>
                                <p class="text-lg font-semibold">Detailed metrics on activities, lessons and courses</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-full bg-success-100 dark:bg-success-900 p-3">
                                <x-heroicon-o-document-text class="w-6 h-6 text-success-600 dark:text-success-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Assessment</p>
                                <p class="text-lg font-semibold">Examination results and student performance</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-4">
                            <div class="rounded-full bg-warning-100 dark:bg-warning-900 p-3">
                                <x-heroicon-o-user-group class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Engagement</p>
                                <p class="text-lg font-semibold">User participation and communication metrics</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page> 