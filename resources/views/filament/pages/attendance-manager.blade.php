<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex flex-col lg:flex-row justify-between items-start space-y-4 lg:space-y-0">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $team->name }} - Attendance</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Mark attendance for {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-2">
                    <x-filament::input.wrapper class="w-full sm:w-auto">
                        {{-- <x-filament::input-label for="date-picker">Select Date</x-filament::input.label> --}}
                        <x-filament::input type="date" id="date-picker" wire:model="date" wire:change="updateDate($event.target.value)" class="w-full" />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </div>
        
        {{-- Attendance Statistics --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Attendance Overview</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</div>
                    <div class="text-2xl font-bold text-gray-800 dark:text-white">{{ $stats['total_students'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">students</div>
                </div>
                <div class="bg-success-100 dark:bg-success-900/20 p-4 rounded-lg">
                    <div class="text-sm font-medium text-success-600 dark:text-success-400">Present</div>
                    <div class="text-2xl font-bold text-success-700 dark:text-success-400">{{ $stats['present'] }}</div>
                    <div class="text-xs text-success-600 dark:text-success-400 mt-1">
                        {{ $stats['total_students'] > 0 ? round(($stats['present'] / $stats['total_students']) * 100) : 0 }}%
                    </div>
                </div>
                <div class="bg-danger-100 dark:bg-danger-900/20 p-4 rounded-lg">
                    <div class="text-sm font-medium text-danger-600 dark:text-danger-400">Absent</div>
                    <div class="text-2xl font-bold text-danger-700 dark:text-danger-400">{{ $stats['absent'] }}</div>
                    <div class="text-xs text-danger-600 dark:text-danger-400 mt-1">
                        {{ $stats['total_students'] > 0 ? round(($stats['absent'] / $stats['total_students']) * 100) : 0 }}%
                    </div>
                </div>
                <div class="bg-warning-100 dark:bg-warning-900/20 p-4 rounded-lg">
                    <div class="text-sm font-medium text-warning-600 dark:text-warning-400">Late</div>
                    <div class="text-2xl font-bold text-warning-700 dark:text-warning-400">{{ $stats['late'] }}</div>
                    <div class="text-xs text-warning-600 dark:text-warning-400 mt-1">
                        {{ $stats['total_students'] > 0 ? round(($stats['late'] / $stats['total_students']) * 100) : 0 }}%
                    </div>
                </div>
                <div class="bg-info-100 dark:bg-info-900/20 p-4 rounded-lg">
                    <div class="text-sm font-medium text-info-600 dark:text-info-400">Excused</div>
                    <div class="text-2xl font-bold text-info-700 dark:text-info-400">{{ $stats['excused'] }}</div>
                    <div class="text-xs text-info-600 dark:text-info-400 mt-1">
                        {{ $stats['total_students'] > 0 ? round(($stats['excused'] / $stats['total_students']) * 100) : 0 }}%
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="flex flex-wrap gap-2">
                    <x-filament::button color="success" wire:click="markAllWithStatus('present')">
                        <x-heroicon-m-check class="w-4 h-4 mr-1" /> Mark All Present
                    </x-filament::button>
                    <x-filament::button color="danger" wire:click="markAllWithStatus('absent')">
                        <x-heroicon-m-x-mark class="w-4 h-4 mr-1" /> Mark All Absent
                    </x-filament::button>
                    <x-filament::button color="warning" wire:click="markAllWithStatus('late')">
                        <x-heroicon-m-clock class="w-4 h-4 mr-1" /> Mark All Late
                    </x-filament::button>
                    <x-filament::button color="info" wire:click="markAllWithStatus('excused')">
                        <x-heroicon-m-document-check class="w-4 h-4 mr-1" /> Mark All Excused
                    </x-filament::button>
                </div>
            </div>
        </div>
        
        {{-- QR Code Manager --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    <span class="flex items-center">
                        <x-heroicon-o-qr-code class="w-5 h-5 mr-2" />
                        QR Code Attendance
                    </span>
                </h3>
                <x-filament::button wire:click="toggleShowQrCode">
                    {{ $showQrCode ? 'Hide QR Code' : 'Show QR Code Manager' }}
                </x-filament::button>
            </div>
            
            @if($showQrCode)
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    @if($activeQrCode)
                        <div class="mb-4 p-4 bg-success-50 dark:bg-success-900/20 border border-success-100 dark:border-success-700 rounded-lg">
                            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                                <div>
                                    <h4 class="font-medium text-success-700 dark:text-success-400 text-lg flex items-center">
                                        <x-heroicon-s-check-badge class="w-5 h-5 mr-1" />
                                        Active QR Code
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $activeQrCode->description }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Expires: {{ \Carbon\Carbon::parse($activeQrCode->expires_at)->format('g:i A') }} 
                                        ({{ \Carbon\Carbon::parse($activeQrCode->expires_at)->diffForHumans() }})
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <x-filament::button size="sm" color="info" wire:click="extendQrCodeExpiry(15)">
                                        <x-heroicon-m-clock class="w-4 h-4 mr-1" /> +15min
                                    </x-filament::button>
                                    <x-filament::button size="sm" color="danger" wire:click="deactivateQrCode">
                                        <x-heroicon-m-stop class="w-4 h-4 mr-1" /> Deactivate
                                    </x-filament::button>
                                </div>
                            </div>
                            
                            <div class="flex flex-col md:flex-row items-center justify-center mt-6 space-y-4 md:space-y-0 md:space-x-6">
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div class="text-center mb-2 font-medium">Scan code or share link:</div>
                                    <div class="mb-4">
                                        <div class="flex justify-center">
                                            <x-safe-qr-code :url="route('attendance.scan', ['code' => $activeQrCode->code])" :size="200" />
                                        </div>
                                    </div>
                                    <div class="text-center text-sm truncate max-w-xs p-2 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                                        {{ route('attendance.scan', ['code' => $activeQrCode->code]) }}
                                    </div>
                                </div>
                                
                                <div class="max-w-md">
                                    <h5 class="font-medium text-gray-900 dark:text-white mb-2">How it works:</h5>
                                    <ol class="text-sm text-gray-600 dark:text-gray-400 list-decimal space-y-2 pl-5">
                                        <li>Students scan the QR code with their mobile device</li>
                                        <li>They will be prompted to sign in if not already</li>
                                        <li>Attendance is automatically recorded as "present"</li>
                                        <li>Students can't mark themselves as absent or late</li>
                                    </ol>
                                    <div class="mt-4 px-3 py-2 bg-info-50 dark:bg-info-900/10 text-info-700 dark:text-info-300 text-sm rounded-lg border border-info-200 dark:border-info-800">
                                        <p><strong>Tip:</strong> Share the QR code or link with your students via your preferred method.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <div class="mb-4">
                                    <x-filament::input.wrapper>
                                        {{-- <x-filament::input.label for="qr-description">Description (optional)</x-filament::input.label> --}}
                                        <x-filament::input type="text" id="qr-description" wire:model="qrCodeDescription" placeholder="E.g., Morning Attendance" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div class="mb-4">
                                    <x-filament::input.wrapper>
                                        {{-- <x-filament::input.label for="qr-expiry">QR Code Valid For</x-filament::input.label> --}}
                                        <x-filament::input.select id="qr-expiry" wire:model="qrCodeExpiryMinutes">
                                            <option value="15">15 minutes</option>
                                            <option value="30">30 minutes</option>
                                            <option value="60">1 hour</option>
                                            <option value="120">2 hours</option>
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                                <x-filament::button color="primary" wire:click="generateQrCode">
                                    <x-heroicon-m-qr-code class="w-4 h-4 mr-1" /> Generate QR Code
                                </x-filament::button>
                            </div>
                            
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-2">QR Code Attendance</h5>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Generate a QR code that students can scan to mark themselves present. This is ideal for:
                                </p>
                                <ul class="mt-2 text-sm text-gray-600 dark:text-gray-400 list-disc pl-5 space-y-1">
                                    <li>Large classes</li>
                                    <li>Self-check-in scenarios</li>
                                    <li>Reducing manual attendance tracking</li>
                                    <li>In-person or hybrid classes</li>
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
        
        {{-- Attendance List --}}
        <div x-data="{ 
            selectAll: false,
            selectedStudents: [],
            toggleAll() {
                this.selectAll = !this.selectAll;
                this.selectedStudents = this.selectAll ? [...document.querySelectorAll('[data-student-id]')].map(el => el.getAttribute('data-student-id')) : [];
            }
        }" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Student Attendance</h3>
                    
                    <div x-show="selectedStudents.length > 0" x-cloak class="flex flex-wrap gap-2">
                        <div class="bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full text-sm text-gray-700 dark:text-gray-300">
                            <span x-text="selectedStudents.length"></span> students selected
                        </div>
                        <x-filament::button size="sm" color="success" x-on:click="$wire.batchMarkAttendance(selectedStudents, 'present'); selectedStudents = []; selectAll = false;">
                            <x-heroicon-m-check class="w-4 h-4 mr-1" /> Mark Present
                        </x-filament::button>
                        <x-filament::button size="sm" color="danger" x-on:click="$wire.batchMarkAttendance(selectedStudents, 'absent'); selectedStudents = []; selectAll = false;">
                            <x-heroicon-m-x-mark class="w-4 h-4 mr-1" /> Mark Absent
                        </x-filament::button>
                        <x-filament::button size="sm" color="warning" x-on:click="$wire.batchMarkAttendance(selectedStudents, 'late'); selectedStudents = []; selectAll = false;">
                            <x-heroicon-m-clock class="w-4 h-4 mr-1" /> Mark Late
                        </x-filament::button>
                        <x-filament::button size="sm" color="info" x-on:click="$wire.batchMarkAttendance(selectedStudents, 'excused'); selectedStudents = []; selectAll = false;">
                            <x-heroicon-m-document-check class="w-4 h-4 mr-1" /> Mark Excused
                        </x-filament::button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs">
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                        x-model="selectAll" 
                                        x-on:click="toggleAll()"
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-primary-500">
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Student
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Time In/Out
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Notes
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($students as $student)
                            <tr data-student-id="{{ $student->id }}" 
                                :class="{ 'bg-primary-50 dark:bg-primary-900/20': selectedStudents.includes('{{ $student->id }}') }"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150">
                                <td class="px-4 py-3">
                                    <input type="checkbox" 
                                        x-model="selectedStudents" 
                                        value="{{ $student->id }}"
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-primary-500">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-700 dark:text-gray-300 font-medium text-sm">
                                                {{ strtoupper(substr($student->name, 0, 2)) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $student->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                ID: {{ $student->student_id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>
                                        <x-filament::input.select
                                            wire:model.live="attendance.{{ $student->id }}.status"
                                            wire:change="saveAttendance('{{ $student->id }}')"
                                        >
                                            <option value="">Select...</option>
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="late">Late</option>
                                            <option value="excused">Excused</option>
                                        </x-filament::input.select>
                                        
                                        @if(!empty($attendance[$student->id]['status']))
                                            <div class="mt-1 flex items-center">
                                                @if($attendance[$student->id]['status'] === 'present')
                                                    <span class="inline-flex items-center rounded-full bg-success-100 dark:bg-success-900/20 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:text-success-400">
                                                        <x-heroicon-s-check-circle class="w-3 h-3 mr-1" /> Present
                                                    </span>
                                                @elseif($attendance[$student->id]['status'] === 'absent')
                                                    <span class="inline-flex items-center rounded-full bg-danger-100 dark:bg-danger-900/20 px-2.5 py-0.5 text-xs font-medium text-danger-800 dark:text-danger-400">
                                                        <x-heroicon-s-x-circle class="w-3 h-3 mr-1" /> Absent
                                                    </span>
                                                @elseif($attendance[$student->id]['status'] === 'late')
                                                    <span class="inline-flex items-center rounded-full bg-warning-100 dark:bg-warning-900/20 px-2.5 py-0.5 text-xs font-medium text-warning-800 dark:text-warning-400">
                                                        <x-heroicon-s-clock class="w-3 h-3 mr-1" /> Late
                                                    </span>
                                                @elseif($attendance[$student->id]['status'] === 'excused')
                                                    <span class="inline-flex items-center rounded-full bg-info-100 dark:bg-info-900/20 px-2.5 py-0.5 text-xs font-medium text-info-800 dark:text-info-400">
                                                        <x-heroicon-s-document-check class="w-3 h-3 mr-1" /> Excused
                                                    </span>
                                                @endif
                                                
                                                @if(!empty($attendance[$student->id]['qr_verified']))
                                                    <span class="ml-1 inline-flex items-center rounded-full bg-primary-100 dark:bg-primary-900/20 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:text-primary-400">
                                                        <x-heroicon-s-qr-code class="w-3 h-3 mr-1" /> QR
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="grid grid-cols-1 gap-2">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 w-12">In:</span>
                                            <x-filament::input 
                                                type="time" 
                                                wire:model.blur="attendance.{{ $student->id }}.time_in"
                                                wire:change="saveAttendance('{{ $student->id }}')"
                                                class="text-sm"
                                            />
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 w-12">Out:</span>
                                            <div class="flex space-x-1 items-center">
                                                <x-filament::input 
                                                    type="time" 
                                                    wire:model.blur="attendance.{{ $student->id }}.time_out"
                                                    wire:change="saveAttendance('{{ $student->id }}')"
                                                    class="text-sm"
                                                />
                                                
                                                @if(!empty($attendance[$student->id]['time_in']) && empty($attendance[$student->id]['time_out']))
                                                    <x-filament::icon-button
                                                        icon="heroicon-o-clock"
                                                        color="primary"
                                                        wire:click="markTimeOut('{{ $student->id }}')"
                                                        tooltip="Mark time out now"
                                                        size="sm"
                                                    />
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <x-filament::input 
                                        type="text" 
                                        wire:model.blur="attendance.{{ $student->id }}.notes"
                                        wire:change="saveAttendance('{{ $student->id }}')"
                                        placeholder="Add notes..."
                                        class="text-sm"
                                    />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end space-x-1">
                                        <x-filament::icon-button
                                            icon="heroicon-o-check"
                                            color="success"
                                            wire:click="markAttendance('{{ $student->id }}', 'present')"
                                            tooltip="Mark present"
                                            size="sm"
                                        />
                                        <x-filament::icon-button
                                            icon="heroicon-o-x-mark"
                                            color="danger"
                                            wire:click="markAttendance('{{ $student->id }}', 'absent')"
                                            tooltip="Mark absent"
                                            size="sm"
                                        />
                                        <x-filament::icon-button
                                            icon="heroicon-o-clock"
                                            color="warning"
                                            wire:click="markAttendance('{{ $student->id }}', 'late')"
                                            tooltip="Mark late"
                                            size="sm"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center py-6">
                                        <x-heroicon-o-user-group class="w-12 h-12 text-gray-400" />
                                        <p class="mt-2 text-sm font-medium">No students found in this class.</p>
                                        <p class="text-xs text-gray-500">Add students to your class or check your filters.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page> 