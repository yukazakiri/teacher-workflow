<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Attendance QR Code Scanner</h2>
                    <p class="text-gray-600 mb-6">Scan this QR code to record your attendance for this class session.</p>
                    
                    <div class="bg-gray-100 rounded-lg p-6 mb-6">
                        <div id="scan-result" class="hidden mb-4 p-4 border rounded-lg">
                            <div id="success-message" class="hidden text-green-700 bg-green-100 p-4 rounded-lg">
                                <svg class="h-6 w-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Your attendance has been successfully recorded!</span>
                            </div>
                            <div id="error-message" class="hidden text-red-700 bg-red-100 p-4 rounded-lg">
                                <svg class="h-6 w-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span id="error-text">An error occurred. Please try again.</span>
                            </div>
                        </div>
                        
                        <form id="attendance-form" class="space-y-4">
                            @csrf
                            <input type="hidden" name="code" value="{{ $code }}">
                            
                            <div class="flex justify-center">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Mark Attendance
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="mt-6 text-sm text-gray-600">
                        <h3 class="font-semibold text-lg mb-2">Instructions:</h3>
                        <ol class="list-decimal pl-5 space-y-2">
                            <li>Ensure you are logged in with your student account</li>
                            <li>Click the "Mark Attendance" button above</li>
                            <li>Your attendance will be automatically recorded for the current session</li>
                            <li>You will see a confirmation message when your attendance is recorded</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('attendance-form');
            const scanResult = document.getElementById('scan-result');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading state
                form.querySelector('button').disabled = true;
                form.querySelector('button').innerText = 'Processing...';
                
                // Send the form data
                fetch('{{ route("attendance.scan.process", ["code" => $code]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({
                        code: '{{ $code }}'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    scanResult.classList.remove('hidden');
                    
                    if (data.message && data.attendance) {
                        // Success case
                        successMessage.classList.remove('hidden');
                        errorMessage.classList.add('hidden');
                    } else {
                        // Error case
                        errorMessage.classList.remove('hidden');
                        successMessage.classList.add('hidden');
                        errorText.innerText = data.message || 'An error occurred. Please try again.';
                    }
                })
                .catch(error => {
                    scanResult.classList.remove('hidden');
                    errorMessage.classList.remove('hidden');
                    successMessage.classList.add('hidden');
                    errorText.innerText = 'Network error. Please try again.';
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Reset button state
                    form.querySelector('button').disabled = false;
                    form.querySelector('button').innerText = 'Mark Attendance';
                });
            });
        });
    </script>
</x-app-layout>
