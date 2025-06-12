<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            {{ $this->form }}
        </x-filament::section>

        @if($showReport)
            <x-filament::section>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-2xl font-bold mb-4">
                        @if($reportType === 'student_profile')
                            Student Profile: {{ $reportData['student']['name'] }}
                        @elseif($reportType === 'activity_report')
                            Activity Report: {{ $reportData['activity']['title'] }}
                        @elseif($reportType === 'student_activities')
                            Student Activities: {{ $reportData['student']['name'] }}
                        @endif
                    </h2>

                    @if($reportType === 'student_profile')
                        @include('filament.admin.reports.student-profile')
                    @elseif($reportType === 'activity_report')
                        @include('filament.admin.reports.activity-report')
                    @elseif($reportType === 'student_activities')
                        @include('filament.admin.reports.student-activities')
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>