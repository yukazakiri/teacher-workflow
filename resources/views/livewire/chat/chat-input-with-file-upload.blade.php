<div class="w-full" 
    x-data="{
        fileUploading: false,
        fileError: null,
        uploadedFileName: null,
        showFilePreview: false,
        uploadProgress: 0,
        
        init() {
            this.$wire.on('pdf-processed', ({fileName}) => {
                this.fileUploading = false;
                this.uploadedFileName = fileName;
                this.showFilePreview = true;
                this.uploadProgress = 100;
            });
            
            this.$wire.on('pdf-error', ({error}) => {
                this.fileUploading = false;
                this.fileError = error;
                this.uploadProgress = 0;
                setTimeout(() => {
                    this.fileError = null;
                }, 5000);
            });
            
            this.$wire.on('pdf-removed', () => {
                this.uploadedFileName = null;
                this.showFilePreview = false;
                this.uploadProgress = 0;
            });
        },
        
        startUpload() {
            if (this.fileUploading) return;
            
            this.fileUploading = true;
            this.uploadProgress = 10;
            
            // Simulate progress for better UX
            const interval = setInterval(() => {
                if (this.uploadProgress < 90) {
                    this.uploadProgress += 10;
                } else if (!this.showFilePreview) {
                    // If file is not processed yet, keep at 90%
                    this.uploadProgress = 90;
                }
                
                if (this.showFilePreview || !this.fileUploading) {
                    clearInterval(interval);
                }
            }, 500);
        }
    }"
>
    <form wire:submit.prevent="sendMessage" class="relative">
        <!-- File upload error message -->
        <div x-show="fileError" x-cloak x-transition class="mb-2 p-2 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 text-xs rounded-md">
            <span x-text="fileError"></span>
        </div>
        
        <!-- File preview -->
        <div x-show="showFilePreview" x-cloak class="mb-2 p-2 bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 rounded-md flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                </svg>
                <span class="text-sm text-primary-700 dark:text-primary-300" x-text="uploadedFileName"></span>
            </div>
            <button type="button" @click="$wire.removeFileContent()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Upload progress bar -->
        <div x-show="fileUploading && !showFilePreview" x-cloak class="mb-2 relative pt-1">
            <div class="flex mb-1 items-center justify-between">
                <div>
                    <span class="text-xs font-semibold inline-block text-primary-600 dark:text-primary-400">
                        Processing PDF
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-xs font-semibold inline-block text-primary-600 dark:text-primary-400" x-text="uploadProgress + '%'"></span>
                </div>
            </div>
            <div class="overflow-hidden h-2 text-xs flex rounded bg-primary-200 dark:bg-primary-800">
                <div 
                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary-500"
                    :style="'width: ' + uploadProgress + '%'"
                ></div>
            </div>
        </div>
        
        <div class="flex rounded-xl shadow-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden transition-all duration-200 focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500">
            <!-- File upload button -->
            <div class="relative">
                <input 
                    type="file" 
                    wire:model.live="file" 
                    id="pdf-upload"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                    accept=".pdf" 
                    :disabled="$wire.disabled || fileUploading"
                    @change="startUpload(); fileUploading = true"
                >
                <button 
                    type="button"
                    class="h-full flex items-center justify-center px-3 py-2 text-gray-500 hover:text-primary-500 dark:text-gray-400 dark:hover:text-primary-400 transition-colors duration-200"
                    :class="{'opacity-50 cursor-not-allowed': $wire.disabled || fileUploading}"
                    title="Upload PDF"
                >
                    <div x-show="!fileUploading" class="flex items-center space-x-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="text-xs hidden sm:inline">PDF</span>
                    </div>
                    <div x-show="fileUploading" x-cloak class="flex items-center space-x-1">
                        <svg class="animate-spin h-5 w-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs hidden sm:inline">Processing... (<span x-text="uploadProgress"></span>%)</span>
                    </div>
                </button>
            </div>

            <!-- Message input with auto-growing height -->
            <div class="relative flex-1">
                <textarea
                    wire:model.live="messageInput"
                    placeholder="Type your message here or upload a PDF..."
                    rows="1"
                    x-data="{
                        resize() {
                            $el.style.height = '0px';
                            $el.style.height = Math.min($el.scrollHeight, 150) + 'px';
                        }
                    }"
                    x-init="resize()"
                    @input="resize()"
                    class="py-3 px-4 block w-full text-sm border-0 focus:ring-0 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-500 resize-none overflow-hidden"
                    :disabled="$wire.disabled"
                ></textarea>
            </div>

            <!-- Send button -->
            <button
                type="submit"
                class="flex-shrink-0 p-2 flex items-center justify-center"
                :class="!$wire.messageInput || $wire.disabled ? 'text-gray-400 cursor-not-allowed' : 'text-primary-500 hover:text-primary-600 dark:text-primary-400 dark:hover:text-primary-300'"
                :disabled="!$wire.messageInput || $wire.disabled"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- Character count, file info and shortcuts -->
        <div class="flex flex-wrap justify-between items-center mt-1 text-xs text-gray-500 dark:text-gray-400 px-2">
            <div class="flex items-center space-x-3">
                <span>
                    <span x-data x-text="$wire.messageInput ? $wire.messageInput.length : 0"></span> / 4000
                </span>
                <span x-show="uploadedFileName" x-cloak class="flex items-center text-primary-600 dark:text-primary-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                    </svg>
                    PDF attached
                </span>
            </div>
            <div class="flex space-x-2 mt-1 sm:mt-0">
                <span>Press <kbd class="px-1 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">Enter</kbd> to send</span>
                <span>Press <kbd class="px-1 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded">Shift + Enter</kbd> for new line</span>
            </div>
        </div>
    </form>

    @script
    <script>
        document.addEventListener('keydown', function(e) {
            // Check if the target is our textarea
            if (e.target.tagName.toLowerCase() === 'textarea' && e.target.getAttribute('wire:model.live') === 'messageInput') {
                // If Enter is pressed without Shift, submit the form
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    Livewire.find(e.target.closest('[wire\\:id]').getAttribute('wire:id')).sendMessage();
                }
            }
        });
    </script>
    @endscript
</div>
