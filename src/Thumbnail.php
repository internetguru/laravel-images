<?php

namespace InternetGuru\LaravelImages;

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\SizeInterface;
use Intervention\Image\Size;

/**
 * How a source image is rendered through the image route: both dimensions crop to
 * their ratio from the centre, one fits that dimension and none fits within the
 * default box. Nothing is upscaled and nothing exceeds the global cap.
 */
class Thumbnail
{
    private const FITS = ['max', 'crop'];

    private function __construct(
        public readonly ?int $width,
        public readonly ?int $height,
        public readonly string $fit,
    ) {}

    public static function sized(?int $width = null, ?int $height = null): self
    {
        $fit = $width !== null && $height !== null ? 'crop' : 'max';
        if ($width === null && $height === null) {
            $width = $height = (int) config('ig-images.default_size');
        }

        return new self($width, $height, $fit);
    }

    /** The image at its own ratio, held only to the global cap. */
    public static function full(): self
    {
        return new self(null, null, 'max');
    }

    /**
     * The rendition a signed request asks for, or null for an unknown fit.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function fromParameters(array $parameters): ?self
    {
        $fit = $parameters['fit'] ?? null;
        if (! in_array($fit, self::FITS, true)) {
            return null;
        }

        return new self(self::dimension($parameters, 'w'), self::dimension($parameters, 'h'), $fit);
    }

    /**
     * Links signed before fit was explicit cropped whenever both dimensions were given.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function fromLegacyParameters(array $parameters): self
    {
        $width = self::dimension($parameters, 'w');
        $height = self::dimension($parameters, 'h');

        return new self($width, $height, $width !== null && $height !== null ? 'crop' : 'max');
    }

    public function url(string $path): string
    {
        $routePath = '/' . trim((string) config('ig-images.route'), '/') . '/' . trim($path, '/');
        $parameters = array_filter(
            ['w' => $this->width, 'h' => $this->height, 'fit' => $this->fit],
            fn ($value) => $value !== null
        );
        $parameters[UrlSigner::PARAMETER] = app(UrlSigner::class)->signature($routePath, $parameters);

        return $routePath . '?' . http_build_query($parameters);
    }

    /**
     * The signed URL with the pixel size it renders at, the requested size when the image can't be read,
     * and a srcset adding the double-density rendition when the source is large enough to give one.
     *
     * @return array{url: string, width: int, height: int, srcset: string|null}
     */
    public function forImage(string $path): array
    {
        $url = $this->url($path);
        [$width, $height] = ImageDimensions::of(ImagesServiceProvider::sourceDisk()->path($path));
        if ($width === 0 || $height === 0) {
            return [
                'url' => $url,
                'width' => $this->width ?? $this->height ?? 0,
                'height' => $this->height ?? $this->width ?? 0,
                'srcset' => null,
            ];
        }

        $source = new Size($width, $height);
        $rendered = $this->renderedSize($source);
        $doubled = $this->doubled();
        $density = round($doubled->renderedSize($source)->width() / $rendered->width(), 2);

        return [
            'url' => $url,
            'width' => $rendered->width(),
            'height' => $rendered->height(),
            'srcset' => $density > 1 ? "{$url} 1x, {$doubled->url($path)} {$density}x" : null,
        ];
    }

    public function renderedSize(SizeInterface $source): SizeInterface
    {
        $maxSize = (int) config('ig-images.max_size');
        $capped = $source->scaleDown($maxSize, $maxSize);

        if ($this->fit === 'crop' && $this->width !== null && $this->height !== null) {
            return (new Size($this->width, $this->height))
                ->contain($capped->width(), $capped->height())
                ->resizeDown($this->width, $this->height);
        }

        return $capped->scaleDown($this->width, $this->height);
    }

    /** Resample once, straight to the rendered size. */
    public function render(ImageInterface $image): ImageInterface
    {
        $source = $image->size();
        $rendered = $this->renderedSize($source);
        if ($rendered->width() === $source->width() && $rendered->height() === $source->height()) {
            return $image;
        }

        return $this->fit === 'crop'
            ? $image->cover($rendered->width(), $rendered->height())
            : $image->resize($rendered->width(), $rendered->height());
    }

    /** Distinguishes renditions in the cache. */
    public function key(): string
    {
        return $this->width . $this->height . $this->fit;
    }

    private function doubled(): self
    {
        return new self(
            $this->width === null ? null : $this->width * 2,
            $this->height === null ? null : $this->height * 2,
            $this->fit,
        );
    }

    /** @param  array<string, mixed>  $parameters */
    private static function dimension(array $parameters, string $key): ?int
    {
        return filled($parameters[$key] ?? null) ? max(1, (int) $parameters[$key]) : null;
    }
}
