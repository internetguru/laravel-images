<?php

namespace InternetGuru\LaravelImages;

use Illuminate\Support\Str;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Laravel\Facades\Image;

/** Renders a source image into a cached rendition. */
class ImageRenderer
{
    /** Written under a temporary name and renamed, so a concurrent request never reads a partial file. */
    public function renderToCache(string $sourcePath, Thumbnail $thumbnail, string $format, string $cachePath): EncodedImageInterface
    {
        $image = $thumbnail->render(Image::decodePath($sourcePath));
        $mimeType = self::mimeType($format);
        $quality = config("ig-images.quality.{$format}");
        $encoded = $quality !== null
            ? $image->encodeUsingMediaType($mimeType, quality: (int) $quality)
            : $image->encodeUsingMediaType($mimeType);

        $disk = ImagesServiceProvider::cacheDisk();
        $temporaryPath = $cachePath . '.' . Str::random(8) . '.tmp';
        $disk->put($temporaryPath, (string) $encoded);
        $disk->move($temporaryPath, $cachePath);

        return $encoded;
    }

    public static function mimeType(string $format): string
    {
        return $format === 'jpg' ? 'image/jpeg' : "image/{$format}";
    }
}
