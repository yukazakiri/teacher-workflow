<div class="p-4 bg-white rounded-lg shadow">
    <div class="flex flex-col lg:flex-row justify-between mb-6 space-y-4 lg:space-y-0">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Attendance Manager</h2>
            <p class="text-sm text-gray-600">{{ $team->name }} - {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
        </div>
        
        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
            <div>
                <x-label for="date-picker" value="Date" />
                <x-input id="date-picker" type="date" class="block mt-1" wire:model.live="date" wire:change="updateDate($event.target.value)" />
            </div>
            
            <div>
                <x-label for="attendance-status" value="Quick Mark" />
                <div class="mt-1 flex space-x-1">
                    <x-button class="bg-green-500 hover:bg-green-600" wire:click="markAllWithStatus('present')">
                        All Present
                    </x-button>
                    <x-button class="bg-red-500 hover:bg-red-600" wire:click="markAllWithStatus('absent')">
                        All Absent
                    </x-button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance Statistics -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-2">Today's Statistics</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-gray-100 p-3 rounded-lg">
                <div class="text-sm text-gray-500">Total</div>
                <div class="text-xl font-bold">{{ $stats['total_students'] }}</div>
            </div>
            <div class="bg-green-100 p-3 rounded-lg">
                <div class="text-sm text-green-600">Present</div>
                <div class="text-xl font-bold text-green-700">{{ $stats['present'] }}</div>
            </div>
            <div class="bg-red-100 p-3 rounded-lg">
                <div class="text-sm text-red-600">Absent</div>
                <div class="text-xl font-bold text-red-700">{{ $stats['absent'] }}</div>
            </div>
            <div class="bg-yellow-100 p-3 rounded-lg">
                <div class="text-sm text-yellow-600">Late</div>
                <div class="text-xl font-bold text-yellow-700">{{ $stats['late'] }}</div>
            </div>
            <div class="bg-blue-100 p-3 rounded-lg">
                <div class="text-sm text-blue-600">Excused</div>
                <div class="text-xl font-bold text-blue-700">{{ $stats['excused'] }}</div>
            </div>
        </div>
    </div>
    
    <!-- QR Code Manager -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">QR Code Attendance</h3>
            <x-button wire:click="toggleShowQrCode">
                {{ $showQrCode ? 'Hide QR Code' : 'Show QR Code Manager' }}
            </x-button>
        </div>
        
        @if($showQrCode)
            <div class="bg-white p-4 rounded-lg shadow-sm">
                @if($activeQrCode)
                    <div class="mb-4 p-3 bg-green-50 border border-green-100 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-medium text-green-700">Active QR Code</h4>
                                <p class="text-sm text-gray-600">{{ $activeQrCode->description }}</p>
                                <p class="text-xs text-gray-500">
                                    Expires: {{ \Carbon\Carbon::parse($activeQrCode->expires_at)->format('g:i A') }} 
                                    ({{ \Carbon\Carbon::parse($activeQrCode->expires_at)->diffForHumans() }})
                                </p>
                            </div>
                            <div class="space-x-2">
                                <x-button wire:click="extendQrCodeExpiry(15)" class="bg-blue-500 hover:bg-blue-600">
                                    +15min
                                </x-button>
                                <x-button wire:click="deactivateQrCode" class="bg-red-500 hover:bg-red-600">
                                    Deactivate
                                </x-button>
                            </div>
                        </div>
                        
                        <div class="flex flex-col md:flex-row items-center justify-center mt-4 space-y-4 md:space-y-0 md:space-x-4">
                            <div class="bg-white p-2 rounded-lg border">
                                <div class="text-center mb-2">Scan code or share link:</div>
                                <!-- QR Code will be displayed here -->
                                <div class="text-center text-sm">
                                    {{ route('attendance.scan', ['code' => $activeQrCode->code]) }}
                                </div>
                            </div>
                            
                            <div class="max-w-md">
                                <p class="text-sm text-gray-700 mb-2">Instructions:</p>
                                <ol class="text-sm text-gray-600 list-decimal pl-5">
                                    <li>Students scan the QR code with their mobile device</li>
                                    <li>They will be prompted to sign in if not already</li>
                                    <li>Attendance is automatically recorded</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-4">
                        <x-label for="qr-description" value="Description (optional)" />
                        <x-input id="qr-description" type="text" class="block w-full mt-1" wire:model="qrCodeDescription" placeholder="E.g., Morning Attendance" />
                    </div>
                    <div class="mb-4">
                        <x-label for="qr-expiry" value="QR Code Valid For" />
                        <select id="qr-expiry" class="block w-full mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" wire:model="qrCodeExpiryMinutes">
                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="60">1 hour</option>
                            <option value="120">2 hours</option>
                        </select>
                    </div>
                    <x-button wire:click="generateQrCode" class="bg-indigo-500 hover:bg-indigo-600">
                        Generate QR Code
                    </x-button>
                @endif
            </div>
        @endif
    </div>
    
    <!-- Attendance List -->
    <div>
        <h3 class="text-lg font-semibold mb-2">Student Attendance</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Student
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Time In
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Time Out
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Notes
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($students as $student)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $student->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $student->student_id }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select 
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" 
                                    wire:model.live="attendance.{{ $student->id }}.status"
                                    wire:change="saveAttendance('{{ $student->id }}')"
                                >
                                    <option value="">Select...</option>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                    <option value="excused">Excused</option>
                                </select>
                                
                                @if(!empty($attendance[$student->id]['qr_verified']))
                                    <span class="inline-block ml-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input 
                                    type="time" 
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    wire:model.blur="attendance.{{ $student->id }}.time_in"
                                    wire:change="saveAttendance('{{ $student->id }}')"
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex space-x-2 items-center">
                                    <input 
                                        type="time" 
                                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        wire:model.blur="attendance.{{ $student->id }}.time_out"
                                        wire:change="saveAttendance('{{ $student->id }}')"
                                    >
                                    
                                    @if(!empty($attendance[$student->id]['time_in']) && empty($attendance[$student->id]['time_out']))
                                        <button 
                                            wire:click="markTimeOut('{{ $student->id }}')"
                                            class="p-1 text-sm text-blue-600 hover:text-blue-800"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <input 
                                    type="text" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    wire:model.blur="attendance.{{ $student->id }}.notes"
                                    wire:change="saveAttendance('{{ $student->id }}')"
                                    placeholder="Add notes..."
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-1 justify-end">
                                    <button wire:click="markAttendance('{{ $student->id }}', 'present')" class="p-1 text-green-600 hover:text-green-900">
                                        <span class="sr-only">Present</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button wire:click="markAttendance('{{ $student->id }}', 'absent')" class="p-1 text-red-600 hover:text-red-900">
                                        <span class="sr-only">Absent</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button wire:click="markAttendance('{{ $student->id }}', 'late')" class="p-1 text-yellow-600 hover:text-yellow-900">
                                        <span class="sr-only">Late</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
