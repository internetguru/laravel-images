<?php

namespace InternetGuru\LaravelImages;

use Illuminate\Support\Facades\Cache;

class ImageDimensions
{
    /**
     * Width and height of an image file, zero when it can't be read, cached per file revision.
     *
     * @return array{0: int, 1: int}
     */
    public static function of(string $path): array
    {
        $revision = @filemtime($path) ?: 0;

        return Cache::rememberForever(
            'image-dimensions:' . md5($path) . ':' . $revision,
            function () use ($path) {
                $size = @getimagesize($path);

                return $size === false ? [0, 0] : [$size[0], $size[1]];
            }
        );
    }
}
