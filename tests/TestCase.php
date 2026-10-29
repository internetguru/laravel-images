<?php

namespace Tests;

use Illuminate\Foundation\Application;
use InternetGuru\LaravelImages\ImagesServiceProvider;
use Intervention\Image\Laravel\ServiceProvider as InterventionServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            InterventionServiceProvider::class,
            ImagesServiceProvider::class,
        ];
    }

    /** @param  Application  $app */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
    }

    protected function fakeJpegBinary(int $width = 10, int $height = 10): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 100, 50));
        ob_start();
        imagejpeg($image);

        return ob_get_clean();
    }

    /** @return array{0: int, 1: int} */
    protected function renderedSize(string $binary): array
    {
        $size = getimagesizefromstring($binary);

        return [$size[0], $size[1]];
    }
}
