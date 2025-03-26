<?php

declare(strict_types=1);

namespace App\Helpers;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;

class QrCodeHelper
{
    /**
     * Generate a QR code using BaconQrCode directly
     * 
     * @param string $data The data to encode in the QR code
     * @param int $size The size of the QR code
     * @return string SVG content of the QR code
     */
    public static function generateSvg(string $data, int $size = 200): string
    {
        try {
            // Sanitize and clean data for UTF-8 issues
            $sanitizedData = preg_replace('/[\x00-\x1F\x7F]/u', '', $data);
            $cleanData = mb_convert_encoding($sanitizedData, 'UTF-8', 'UTF-8');
            
            // Ensure the string is valid UTF-8 - if not, convert to ASCII
            if (!mb_check_encoding($cleanData, 'UTF-8')) {
                $cleanData = mb_convert_encoding($sanitizedData, 'ASCII', 'UTF-8');
            }
            
            // Create a renderer with specific options
            $renderer = new ImageRenderer(
                new RendererStyle($size, 4), // Just set size and margin
                new SvgImageBackEnd()
            );
            
            $writer = new Writer($renderer);
            return $writer->writeString($cleanData);
        } catch (\Throwable $e) {
            Log::error('QR code generation failed: ' . $e->getMessage());
            return self::fallbackQrCodeSvg($size);
        }
    }
    
    /**
     * Generate a fallback QR code SVG when the actual generation fails
     * 
     * @param int $size The size of the SVG
     * @return string An SVG representation of a "failed" QR code
     */
    private static function fallbackQrCodeSvg(int $size = 200): string
    {
        // Create a simple SVG with an error message
        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="{$size}" height="{$size}" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="#f4f4f5" />
            <rect x="10" y="10" width="80" height="80" fill="#e4e4e7" />
            <text x="50" y="45" font-family="Arial" font-size="8" text-anchor="middle" fill="#71717a">QR Code</text>
            <text x="50" y="55" font-family="Arial" font-size="8" text-anchor="middle" fill="#71717a">Generation Failed</text>
        </svg>
        SVG;
    }
} 