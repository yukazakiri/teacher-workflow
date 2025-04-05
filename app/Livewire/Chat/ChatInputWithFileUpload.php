<?php

namespace App\Livewire\Chat;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\PdfToText\Pdf;

class ChatInputWithFileUpload extends Component
{
    use WithFileUploads;

    public $messageInput = '';

    public $disabled = false;

    public $file = null;

    public $fileContent = null;

    public $fileProcessing = false;

    public $fileError = null;

    public $uploadedFileName = null;

    public $pdfPath = null;

    public function mount($disabled = false)
    {
        $this->disabled = $disabled;
    }

    public function updatedFile()
    {
        if (! $this->file) {
            return;
        }

        $this->validate([
            'file' => 'nullable|file|mimes:pdf|max:10240', // 10MB max
        ]);

        $this->fileProcessing = true;
        $this->fileError = null;

        try {
            $this->uploadedFileName = $this->file->getClientOriginalName();

            // Store the file temporarily
            $path = $this->file->store('temp-pdf-uploads');
            $this->pdfPath = $path;
            $fullPath = Storage::path($path);

            // Log the file details for debugging
            Log::info('Processing PDF file', [
                'filename' => $this->uploadedFileName,
                'path' => $fullPath,
                'size' => filesize($fullPath),
            ]);

            // Try to extract text with different methods
            $this->fileContent = $this->extractPdfText($fullPath);

            // Append file content to message input with a note
            $this->messageInput .= "\n\n[PDF Content from: {$this->uploadedFileName}]\n".$this->fileContent;

            $this->dispatch('pdf-processed', fileName: $this->uploadedFileName);
        } catch (\Exception $e) {
            Log::error('PDF processing error', [
                'error' => $e->getMessage(),
                'file' => $this->uploadedFileName,
            ]);

            $this->fileError = 'Error processing PDF: '.$e->getMessage();
            $this->dispatch('pdf-error', error: $this->fileError);
        } finally {
            $this->fileProcessing = false;
            $this->file = null; // Reset the file input
        }
    }

    /**
     * Extract text from PDF using multiple methods for better reliability
     *
     * @param  string  $pdfPath  Full path to the PDF file
     * @return string Extracted text content
     */
    protected function extractPdfText(string $pdfPath): string
    {
        // Method 1: Try with default options
        try {
            $text = (new Pdf)
                ->setPdf($pdfPath)
                ->text();

            if (! empty(trim($text))) {
                return $text;
            }
        } catch (\Exception $e) {
            Log::warning('First PDF extraction method failed', ['error' => $e->getMessage()]);
        }

        // Method 2: Try with layout option
        try {
            $text = (new Pdf)
                ->setPdf($pdfPath)
                ->setOptions([
                    'layout' => true,
                    'quiet' => true,
                ])
                ->text();

            if (! empty(trim($text))) {
                return $text;
            }
        } catch (\Exception $e) {
            Log::warning('Second PDF extraction method failed', ['error' => $e->getMessage()]);
        }

        // Method 3: Try with raw option
        try {
            $text = (new Pdf)
                ->setPdf($pdfPath)
                ->setOptions([
                    'raw' => true,
                ])
                ->text();

            if (! empty(trim($text))) {
                return $text;
            }
        } catch (\Exception $e) {
            Log::warning('Third PDF extraction method failed', ['error' => $e->getMessage()]);
        }

        // If all methods fail or return empty content, return a fallback message
        return '[PDF content could not be extracted. The file may be scanned, contain images only, or be password protected.]';
    }

    public function removeFileContent()
    {
        // Remove the PDF content from the message
        if ($this->uploadedFileName) {
            $pattern = "/\n\n\[PDF Content from: {$this->uploadedFileName}\]\n.*$/s";
            $this->messageInput = preg_replace($pattern, '', $this->messageInput);

            // Clean up the temporary file if it exists
            if ($this->pdfPath && Storage::exists($this->pdfPath)) {
                Storage::delete($this->pdfPath);
            }

            $this->uploadedFileName = null;
            $this->fileContent = null;
            $this->pdfPath = null;
            $this->dispatch('pdf-removed');
        }
    }

    public function sendMessage()
    {
        if (empty(trim($this->messageInput)) || $this->disabled) {
            return;
        }

        $this->dispatch('send-message', message: $this->messageInput);
        $this->messageInput = '';

        // Clean up any temporary files
        if ($this->pdfPath && Storage::exists($this->pdfPath)) {
            Storage::delete($this->pdfPath);
        }

        $this->uploadedFileName = null;
        $this->fileContent = null;
        $this->pdfPath = null;
    }

    #[On('resetConversation')]
    public function resetConversation()
    {
        $this->dispatch('reset-conversation');
        $this->messageInput = '';

        // Clean up any temporary files
        if ($this->pdfPath && Storage::exists($this->pdfPath)) {
            Storage::delete($this->pdfPath);
        }

        $this->uploadedFileName = null;
        $this->fileContent = null;
        $this->pdfPath = null;
    }

    public function render()
    {
        return view('livewire.chat.chat-input-with-file-upload');
    }
}
