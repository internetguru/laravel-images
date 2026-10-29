<?php

use InternetGuru\LaravelImages\Thumbnail;

if (! function_exists('thumbnail')) {
    /**
     * A signed URL for an image on the source disk fitted to the given size, with the pixel
     * size it renders at and a double-density srcset where the source is large enough.
     *
     * @return array{url: string, width: int, height: int, srcset: string|null}
     */
    function thumbnail(string $path, ?int $width = null, ?int $height = null): array
    {
        return Thumbnail::sized($width, $height)->forImage($path);
    }
}

if (! function_exists('fullImage')) {
    /**
     * A signed URL for an image on the source disk at its own ratio, held only to the
     * global size cap, with the pixel size it renders at (zero when it can't be read).
     *
     * @return array{url: string, width: int, height: int, srcset: string|null}
     */
    function fullImage(string $path): array
    {
        return Thumbnail::full()->forImage($path);
    }
}
