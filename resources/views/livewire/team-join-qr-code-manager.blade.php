<div>


    {{-- Instant Join QR Code Manager --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Instant Join QR Code
        </x-slot>

        <x-slot name="description">
            Generate a temporary QR code that allows users to join this team instantly without approval.
        </x-slot>

        <div class="mb-4">
            <x-filament::button wire:click="toggleShowQrCode">
                {{ $showQrCode ? 'Hide QR Code Manager' : 'Show QR Code Manager' }}
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
                                @if($activeQrCode->use_limit)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Uses: {{ $activeQrCode->use_count }} / {{ $activeQrCode->use_limit }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex space-x-2">
                                <x-filament::button size="sm" color="info" wire:click="extendQrCodeExpiry(30)">
                                    <x-heroicon-m-clock class="w-4 h-4 mr-1" /> +30min
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
                                        <x-safe-qr-code :url="route('teams.join.instant', ['code' => $activeQrCode->code])" :size="200" />
                                    </div>
                                </div>
                                <div class="text-center text-sm truncate max-w-xs p-2 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                                    {{ route('teams.join.instant', ['code' => $activeQrCode->code]) }}
                                </div>
                            </div>
                            
                            <div class="max-w-md">
                                <h5 class="font-medium text-gray-900 dark:text-white mb-2">How it works:</h5>
                                <ol class="text-sm text-gray-600 dark:text-gray-400 list-decimal space-y-2 pl-5">
                                    <li>Users scan the QR code with their mobile device</li>
                                    <li>They will be prompted to sign in if not already</li>
                                    <li>Once authenticated, they'll be added to the team instantly</li>
                                    <li>This QR code expires automatically</li>
                                </ol>
                                <div class="mt-4 px-3 py-2 bg-info-50 dark:bg-info-900/10 text-info-700 dark:text-info-300 text-sm rounded-lg border border-info-200 dark:border-info-800">
                                    <p><strong>Tip:</strong> Use this for in-person registration events or when you want to quickly add multiple users.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <x-filament::input.wrapper label="Description (optional)">
                                    <x-filament::input type="text" id="qr-description" wire:model="qrCodeDescription" placeholder="E.g., Class registration" />
                                </x-filament::input.wrapper>
                            </div>
                            <div class="mb-4">
                                <x-filament::input.wrapper label="QR Code Valid For">
                                    <x-filament::input.select id="qr-expiry" wire:model="qrCodeExpiryMinutes">
                                        <option value="30">30 minutes</option>
                                        <option value="60">1 hour</option>
                                        <option value="120">2 hours</option>
                                        <option value="1440">1 day</option>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                            <div class="mb-4">
                                <x-filament::input.wrapper label="Use Limit (optional)" helper-text="Maximum number of times this QR code can be used">
                                    <x-filament::input type="number" id="qr-use-limit" wire:model="qrCodeUseLimit" placeholder="Leave empty for unlimited" min="1" />
                                </x-filament::input.wrapper>
                            </div>
                            <x-filament::button color="primary" wire:click="generateQrCode">
                                <x-heroicon-m-qr-code class="w-4 h-4 mr-1" /> Generate QR Code
                            </x-filament::button>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-2">Instant Join QR Code</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Generate a temporary QR code that allows users to join your team instantly. This is ideal for:
                            </p>
                            <ul class="mt-2 text-sm text-gray-600 dark:text-gray-400 list-disc pl-5 space-y-1">
                                <li>In-person registration events</li>
                                <li>Quick onboarding of multiple users</li>
                                <li>Time-limited enrollment periods</li>
                                <li>Controlled sharing via presentation or handouts</li>
                            </ul>
                            <div class="mt-4 px-3 py-2 bg-warning-50 dark:bg-warning-900/10 text-warning-700 dark:text-warning-300 text-sm rounded-lg border border-warning-200 dark:border-warning-800">
                                <p><strong>Note:</strong> Anyone with this QR code can join your team during the active period. Use with caution.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </x-filament::section>
</div>
