<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow">
            <div class="mb-4">
                <h2 class="text-xl font-bold">Student Grades</h2>
                <p class="text-gray-500 dark:text-gray-400">
                    This spreadsheet displays all student grades for activities in this team.
                    You can edit grades directly by clicking on the cells.
                </p>
                <div class="flex items-center gap-4 mt-2">
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 mr-2">W</span>
                        <span class="text-sm">Written Activity</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 mr-2">P</span>
                        <span class="text-sm">Performance Activity</span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                {{ $this->table }}
            </div>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow">
            <h3 class="text-lg font-medium mb-2">Grading Information</h3>
            <ul class="list-disc list-inside space-y-1 text-sm">
                <li>Written activities are weighted at <strong>{{ $writtenWeight }}%</strong> of the final grade.</li>
                <li>Performance activities are weighted at <strong>{{ $performanceWeight }}%</strong> of the final grade.</li>
                <li>Final grades are calculated based on the weighted average of all graded activities.</li>
                <li>Activities without scores are not included in the average calculations.</li>
                <li>Grades are color-coded: <span class="text-success-600 font-bold">≥90%</span>, <span class="text-primary-600 font-bold">≥80%</span>, <span class="text-warning-600 font-bold">≥70%</span>, <span class="text-danger-600 font-bold">&lt;70%</span></li>
            </ul>
        </div>
    </div>

    <script>
        // Function to update score via AJAX
        function updateScore(input) {
            const studentId = input.dataset.studentId;
            const activityId = input.dataset.activityId;
            const score = input.value.trim() === '' ? null : input.value;

            // Show loading state
            input.disabled = true;
            input.classList.add('opacity-50');

            // Create a Livewire call to update the score
            @this.call('updateScore', studentId, activityId, score)
                .then(() => {
                    // Success - update UI
                    input.disabled = false;
                    input.classList.remove('opacity-50');
                    input.classList.add('bg-green-50');
                    setTimeout(() => {
                        input.classList.remove('bg-green-50');
                    }, 1000);
                })
                .catch(error => {
                    // Error - show error state
                    input.disabled = false;
                    input.classList.remove('opacity-50');
                    input.classList.add('bg-red-50');
                    console.error('Error updating score:', error);

                    // Reset to previous value after error
                    setTimeout(() => {
                        input.classList.remove('bg-red-50');
                    }, 2000);
                });
        }
    </script>
</x-filament-panels::page>
