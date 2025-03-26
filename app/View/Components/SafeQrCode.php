<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Helpers\QrCodeHelper;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Log;

class SafeQrCode extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $url,
        public int $size = 200,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.safe-qr-code');
    }
    
    /**
     * Safely generate a QR code using available packages
     */
    public function safeGenerateQrCode(string $url): string
    {
        // Try the first package (LaravelQRCode) - catches malformed UTF-8 issues
        if (class_exists('LaravelQRCode\Facades\QRCode')) {
            try {
                // First try to convert to clean UTF-8
                $cleanUrl = mb_convert_encoding($url, 'UTF-8', 'UTF-8');
                
                $qrCode = \LaravelQRCode\Facades\QRCode::text($cleanUrl)
                    ->setOutfile(false)
                    ->setSize(8)
                    ->setMargin(2)
                    ->png();
                    
                return '<img src="data:image/png;base64,' . base64_encode($qrCode) . '" alt="QR Code" class="w-48 h-48">';
            } catch (\Throwable $e) {
                Log::error('LaravelQRCode failed: ' . $e->getMessage());
            }
        }
        
        // Use our own helper class which utilizes BaconQrCode directly 
        // with error handling baked in
        return QrCodeHelper::generateSvg($url, $this->size);
    }
} 