<?php

namespace App\Support;

use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;

/** JPEG re-encoding for uploads (Intervention Image v4); strips EXIF such as GPS. */
class Images
{
    /** Square crop for name badges. */
    public static function badge(string $sourcePath, int $size = 600): string
    {
        return (string) Image::decodePath($sourcePath)->cover($size, $size)
            ->encode(new JpegEncoder(quality: 85, strip: true));
    }

    /** Shrink to fit inside $max × $max, never enlarging. */
    public static function fit(string $sourcePath, int $max, int $quality = 85): string
    {
        return (string) Image::decodePath($sourcePath)->scaleDown($max, $max)
            ->encode(new JpegEncoder(quality: $quality, strip: true));
    }
}
