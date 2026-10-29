<?php

namespace InternetGuru\LaravelImages\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use InternetGuru\LaravelImages\ImageRenderer;
use InternetGuru\LaravelImages\ImagesServiceProvider;
use InternetGuru\LaravelImages\Thumbnail;

/** Renders an ageing rendition again so images still in use outlive images:prune-cache. */
class RenewImageRendition implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $sourcePath,
        public Thumbnail $thumbnail,
        public string $format,
        public string $cachePath,
    ) {}

    public function uniqueId(): string
    {
        return $this->cachePath;
    }

    public function handle(ImageRenderer $renderer): void
    {
        if (! is_file($this->sourcePath) || ! ImagesServiceProvider::cacheDisk()->exists($this->cachePath)) {
            return;
        }

        $renderer->renderToCache($this->sourcePath, $this->thumbnail, $this->format, $this->cachePath);
    }
}
