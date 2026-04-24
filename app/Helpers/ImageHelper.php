<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageHelper
{
    /**
     * Clean up temporary images that are not used
     */
    public static function cleanupTempImages(): void
    {
        $tempFiles = Storage::disk('public')->files('blog/temp');
        $expirationTime = now()->subHours(24);
        
        foreach ($tempFiles as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            if ($lastModified < $expirationTime->timestamp) {
                Storage::disk('public')->delete($file);
            }
        }
    }
    
    /**
     * Generate a unique filename
     */
    public static function generateUniqueFilename(string $originalName, string $prefix = 'blog'): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return $prefix . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }
}