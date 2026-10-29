<?php

namespace Tests\Feature;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InternetGuru\LaravelImages\Jobs\RenewImageRendition;
use InternetGuru\LaravelImages\UrlSigner;
use Tests\TestCase;

class ImageControllerTest extends TestCase
{
    private Filesystem $disk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disk = Storage::fake('local');
    }

    public function test_returns_404_for_path_traversal(): void
    {
        $this->get('/img/..%2f..%2fetc%2fpasswd')->assertNotFound();
    }

    public function test_returns_404_when_image_file_does_not_exist(): void
    {
        $this->get($this->signedUrl('does-not-exist.jpg', ['w' => 200, 'fit' => 'max']))->assertNotFound();
    }

    public function test_serves_cached_image_when_present(): void
    {
        $this->disk->put($this->cachePath('photo.jpg', 200, null, 'max'), 'cached-image-bytes');

        $response = $this->get($this->signedUrl('photo.jpg', ['w' => 200, 'fit' => 'max']));

        $response->assertOk();
        $this->assertSame('cached-image-bytes', $response->streamedContent());
    }

    public function test_an_ageing_cached_image_is_served_and_renewed_in_the_background(): void
    {
        Queue::fake();
        $cachePath = $this->cachePath('photo.jpg', 200, null, 'max');
        $this->disk->put($cachePath, 'cached-image-bytes');
        touch($this->disk->path($cachePath), now()->subDays(21)->getTimestamp());

        $response = $this->get($this->signedUrl('photo.jpg', ['w' => 200, 'fit' => 'max']));

        $this->assertSame('cached-image-bytes', $response->streamedContent());
        Queue::assertPushed(RenewImageRendition::class, fn (RenewImageRendition $job) => $job->cachePath === $cachePath);
    }

    public function test_a_recent_cached_image_is_not_renewed(): void
    {
        Queue::fake();
        $this->disk->put($this->cachePath('photo.jpg', 200, null, 'max'), 'cached-image-bytes');

        $this->get($this->signedUrl('photo.jpg', ['w' => 200, 'fit' => 'max']))->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_processes_and_caches_new_image(): void
    {
        $path = $this->storeImage();

        $response = $this->get($this->signedUrl($path, ['w' => 200, 'fit' => 'max']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->disk->assertExists($this->cachePath($path, 200, null, 'max'));
    }

    public function test_a_replaced_source_renders_a_new_rendition(): void
    {
        $path = $this->storeImage();
        $url = $this->signedUrl($path, ['w' => 200, 'fit' => 'max']);
        $this->get($url);
        $stale = $this->cachePath($path, 200, null, 'max');

        touch($this->disk->path($path), time() + 60);
        clearstatcache();
        $this->get($url);

        $this->disk->assertExists($stale);
        $this->disk->assertExists($this->cachePath($path, 200, null, 'max'));
        $this->assertNotSame($stale, $this->cachePath($path, 200, null, 'max'));
    }

    public function test_rendered_images_may_be_cached_by_the_browser(): void
    {
        $response = $this->get($this->signedUrl($this->storeImage(), ['w' => 200, 'fit' => 'max']));

        $response->assertHeader('Cache-Control', 'max-age=86400, public');
        $response->assertHeader('Vary', 'Accept');
        $this->assertNotNull($response->headers->get('ETag'));
    }

    public function test_an_unchanged_cached_image_answers_with_a_304(): void
    {
        $this->disk->put($this->cachePath('photo.jpg', 200, null, 'max'), 'cached-image-bytes');
        $url = $this->signedUrl('photo.jpg', ['w' => 200, 'fit' => 'max']);

        $etag = $this->get($url)->headers->get('ETag');

        $this->get($url, ['If-None-Match' => $etag])->assertStatus(304);
    }

    public function test_an_unsigned_request_is_forbidden(): void
    {
        $this->get('/img/' . $this->storeImage() . '?w=200&fit=max')->assertForbidden();
    }

    public function test_a_tampered_request_is_forbidden(): void
    {
        $url = $this->signedUrl($this->storeImage(), ['w' => 200, 'fit' => 'max']);

        $this->get(str_replace('w=200', 'w=2000', $url))->assertForbidden();
    }

    public function test_a_path_with_spaces_and_diacritics_is_served(): void
    {
        $path = 'kachna s zelím.jpg';
        $this->disk->put($path, $this->fakeJpegBinary());

        $this->get(thumbnail($path, 40)['url'])->assertOk();
    }

    public function test_no_dimensions_fit_the_image_within_800_pixels(): void
    {
        $path = $this->storeImage(1000, 500);

        $thumb = thumbnail($path);
        $response = $this->get($thumb['url']);

        $this->assertStringContainsString('fit=max', $thumb['url']);
        $this->assertSame([800, 400], $this->renderedSize($response->getContent()));
        $this->assertSame([800, 400], [$thumb['width'], $thumb['height']]);
    }

    public function test_one_dimension_scales_proportionally(): void
    {
        $path = $this->storeImage(100, 50);

        $thumb = thumbnail($path, 40);

        $this->assertSame([40, 20], $this->renderedSize($this->get($thumb['url'])->getContent()));
        $this->assertSame([40, 20], [$thumb['width'], $thumb['height']]);
    }

    public function test_both_dimensions_crop_to_their_ratio(): void
    {
        $path = $this->storeImage(100, 50);

        $thumb = thumbnail($path, 40, 40);

        $this->assertStringContainsString('fit=crop', $thumb['url']);
        $this->assertSame([40, 40], $this->renderedSize($this->get($thumb['url'])->getContent()));
        $this->assertSame([40, 40], [$thumb['width'], $thumb['height']]);
    }

    public function test_a_crop_never_upscales(): void
    {
        $path = $this->storeImage(80, 60);

        $thumb = thumbnail($path, 160, 160);

        $this->assertSame([60, 60], $this->renderedSize($this->get($thumb['url'])->getContent()));
        $this->assertSame([60, 60], [$thumb['width'], $thumb['height']]);
    }

    public function test_every_rendition_fits_within_the_size_cap(): void
    {
        $path = $this->storeImage(100, 2600);

        $thumb = thumbnail($path, 100);

        $this->assertSame([98, 2560], $this->renderedSize($this->get($thumb['url'])->getContent()));
        $this->assertSame([98, 2560], [$thumb['width'], $thumb['height']]);
    }

    public function test_a_crop_beyond_the_cap_is_held_to_it(): void
    {
        $path = $this->storeImage(3000, 2400);

        $thumb = thumbnail($path, 3000, 3000);

        $this->assertSame([2048, 2048], $this->renderedSize($this->get($thumb['url'])->getContent()));
        $this->assertSame([2048, 2048], [$thumb['width'], $thumb['height']]);
    }

    public function test_a_full_image_keeps_its_ratio_within_the_size_cap(): void
    {
        $path = $this->storeImage(3000, 1500);

        $full = fullImage($path);

        $this->assertStringNotContainsString('w=', $full['url']);
        $this->assertSame([2560, 1280], [$full['width'], $full['height']]);
        $this->assertSame([2560, 1280], $this->renderedSize($this->get($full['url'])->getContent()));
    }

    public function test_a_large_enough_source_adds_a_double_density_rendition(): void
    {
        $path = $this->storeImage(1000, 1000);

        $thumb = thumbnail($path, 80, 60);
        [$single, $double] = explode(', ', $thumb['srcset']);

        $this->assertSame($thumb['url'] . ' 1x', $single);
        $this->assertStringEndsWith(' 2x', $double);
        $this->assertSame([160, 120], $this->renderedSize($this->get(Str::before($double, ' '))->getContent()));
    }

    public function test_a_source_between_the_densities_states_its_own(): void
    {
        $this->assertStringEndsWith(' 1.25x', thumbnail($this->storeImage(100, 100), 80, 80)['srcset']);
    }

    public function test_a_source_no_larger_than_the_thumbnail_has_no_srcset(): void
    {
        $path = $this->storeImage(80, 60);

        $this->assertNull(thumbnail($path, 80, 60)['srcset']);
        $this->assertNull(fullImage($path)['srcset']);
    }

    public function test_an_unreadable_image_states_the_requested_size(): void
    {
        $thumb = thumbnail('missing.jpg', 40);

        $this->assertSame([40, 40], [$thumb['width'], $thumb['height']]);
        $this->assertNull($thumb['srcset']);
    }

    public function test_a_link_without_fit_is_a_bad_request_under_hmac(): void
    {
        $this->get($this->signedUrl($this->storeImage(), ['w' => 40]))->assertStatus(400);
    }

    public function test_a_glide_link_without_fit_redirects_to_its_former_fit(): void
    {
        config(['ig-images.signature' => 'glide']);
        $path = $this->storeImage();

        $this->get($this->signedUrl($path, ['w' => 40, 'h' => 30]))
            ->assertStatus(301)
            ->assertRedirect($this->signedUrl($path, ['w' => 40, 'h' => 30, 'fit' => 'crop']));
        $this->get($this->signedUrl($path, ['w' => 40]))
            ->assertRedirect($this->signedUrl($path, ['w' => 40, 'fit' => 'max']));
    }

    public function test_an_unknown_fit_is_a_bad_request(): void
    {
        $this->get($this->signedUrl($this->storeImage(), ['w' => 40, 'fit' => 'stretch']))->assertStatus(400);
    }

    public function test_the_best_accepted_format_is_served(): void
    {
        $url = $this->signedUrl($this->storeImage(), ['w' => 200, 'fit' => 'max']);

        $this->get($url, ['Accept' => 'image/avif,image/webp,*/*'])->assertHeader('Content-Type', 'image/avif');
        $this->get($url, ['Accept' => 'image/webp,*/*'])->assertHeader('Content-Type', 'image/webp');
    }

    public function test_a_png_falls_back_to_png_to_keep_transparency(): void
    {
        $image = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($image);
        $this->disk->put('photo.png', ob_get_clean());

        $this->get($this->signedUrl('photo.png', ['w' => 200, 'fit' => 'max']))->assertHeader('Content-Type', 'image/png');
    }

    public function test_the_route_prefix_is_configurable(): void
    {
        config(['ig-images.route' => 'media']);

        $this->assertStringStartsWith('/media/photo.jpg?', thumbnail('photo.jpg', 40)['url']);
    }

    private function storeImage(int $width = 10, int $height = 10): string
    {
        $path = 'photo-' . Str::random(8) . '.jpg';
        $this->disk->put($path, $this->fakeJpegBinary($width, $height));

        return $path;
    }

    /** @param  array<string, int|string>  $parameters */
    private function signedUrl(string $path, array $parameters): string
    {
        $parameters[UrlSigner::PARAMETER] = app(UrlSigner::class)->signature("img/{$path}", $parameters);

        return "/img/{$path}?" . http_build_query($parameters);
    }

    private function cachePath(string $path, ?int $width, ?int $height, string $fit, string $format = 'jpg'): string
    {
        $revision = @filemtime($this->disk->path($path)) ?: 0;

        return 'cache/' . md5($path . $width . $height . $fit . $format . $revision) . '.' . $format;
    }
}
