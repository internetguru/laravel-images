<?php

namespace InternetGuru\LaravelImages\View\Components;

use Illuminate\View\Component;

/**
 * An image from the source disk with everything a browser needs to lay it out
 * before it loads: the signed URL, the rendered size, the sharper rendition and
 * an alt that has to be stated, empty for an image that is only decoration.
 */
class Img extends Component
{
    /** @var array{url: string, width: int, height: int, srcset: string|null} */
    public array $image;

    /**
     * @param  string  $path  Path on the source disk.
     * @param  string  $alt  Text replacing the image; empty for decoration.
     * @param  int|null  $width  Both dimensions crop, one fits, none fits within the default box.
     * @param  bool  $full  The image at its own ratio, ignoring the dimensions.
     * @param  string  $loading  `eager` for images seen without scrolling.
     * @param  string|null  $fetchpriority  `high` for the image the page is judged by.
     */
    public function __construct(
        public string $path,
        public string $alt,
        ?int $width = null,
        ?int $height = null,
        bool $full = false,
        public string $loading = 'lazy',
        public ?string $fetchpriority = null,
    ) {
        $this->image = $full ? fullImage($path) : thumbnail($path, $width, $height);
    }

    public function render(): string
    {
        return <<<'BLADE'
            <img {{ $attributes->merge([
                'src' => $image['url'],
                'srcset' => $image['srcset'],
                'width' => $image['width'],
                'height' => $image['height'],
                'alt' => $alt,
                'loading' => $loading,
                'fetchpriority' => $fetchpriority,
            ]) }}>
            BLADE;
    }
}
