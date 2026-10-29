<?php

namespace InternetGuru\LaravelImages\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InternetGuru\LaravelImages\ImageRenderer;
use InternetGuru\LaravelImages\ImagesServiceProvider;
use InternetGuru\LaravelImages\Jobs\RenewImageRendition;
use InternetGuru\LaravelImages\Thumbnail;
use InternetGuru\LaravelImages\UrlSigner;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    /** Source formats that may carry transparency, kept as PNG when the browser takes neither AVIF nor WebP. */
    private const TRANSPARENT_FORMATS = ['png', 'gif', 'webp'];

    public function show(ImageRenderer $renderer, UrlSigner $signer, string $path, Request $request): Response
    {
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            abort(404);
        }

        if (! $signer->isValid(trim(config('ig-images.route'), '/') . '/' . $path, $request->query())) {
            abort(403);
        }

        if (! $request->has('fit')) {
            abort_unless(config('ig-images.signature') === 'glide', 400);

            return redirect(Thumbnail::fromLegacyParameters($request->only(['w', 'h']))->url($path), 301);
        }

        $thumbnail = Thumbnail::fromParameters($request->only(['w', 'h', 'fit'])) ?? abort(400);
        $format = $this->outputFormat($request, $path);
        $sourcePath = ImagesServiceProvider::sourceDisk()->path($path);
        $cachePath = $this->cachePath($sourcePath, $path, $thumbnail, $format);
        $cacheDisk = ImagesServiceProvider::cacheDisk();

        if ($cacheDisk->exists($cachePath)) {
            if ($cacheDisk->lastModified($cachePath) <= now()->subDays((int) config('ig-images.renew_after_days'))->getTimestamp()) {
                RenewImageRendition::dispatch($sourcePath, $thumbnail, $format, $cachePath);
            }

            return $this->cachedFileResponse($cacheDisk->path($cachePath), $request);
        }

        try {
            $encoded = $renderer->renderToCache($sourcePath, $thumbnail, $format, $cachePath);
        } catch (\Exception $e) {
            abort(404);
        }

        $response = response((string) $encoded, 200)->header('Content-Type', ImageRenderer::mimeType($format));
        $response->setEtag(md5((string) $encoded));
        $this->applyBrowserCache($response);

        return $response;
    }

    /** A replaced source gets a new key, so its stale renditions are left for images:prune-cache. */
    private function cachePath(string $sourcePath, string $path, Thumbnail $thumbnail, string $format): string
    {
        $revision = @filemtime($sourcePath) ?: 0;
        $key = $path . $thumbnail->key() . $format . $revision;

        return config('ig-images.cache_directory') . '/' . md5($key) . ".{$format}";
    }

    /** The best format the browser accepts, keeping transparency in the fallback. */
    private function outputFormat(Request $request, string $path): string
    {
        $accept = $request->header('Accept') ?? '';

        return match (true) {
            str_contains($accept, 'image/avif') => 'avif',
            str_contains($accept, 'image/webp') => 'webp',
            in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::TRANSPARENT_FORMATS, true) => 'png',
            default => 'jpg',
        };
    }

    /** Serve an already rendered image, answering conditional requests with a 304. */
    private function cachedFileResponse(string $absolutePath, Request $request): Response
    {
        $response = response()->file($absolutePath);
        $response->setAutoLastModified();
        $response->setAutoEtag();
        $this->applyBrowserCache($response);
        $response->isNotModified($request);

        return $response;
    }

    private function applyBrowserCache(Response $response): void
    {
        $response->setPublic();
        $response->setMaxAge((int) config('ig-images.browser_cache_seconds'));
        $response->setVary('Accept');
    }
}
