<?php

namespace Tests\Feature;

use InternetGuru\LaravelImages\ImageDimensions;
use Tests\TestCase;

class ImageDimensionsTest extends TestCase
{
    public function test_dimensions_are_cached_per_file_revision(): void
    {
        $path = sys_get_temp_dir() . '/ig-image-' . uniqid() . '.jpg';

        try {
            file_put_contents($path, $this->fakeJpegBinary(64, 48));
            $mtime = filemtime($path);
            $this->assertSame([64, 48], ImageDimensions::of($path));

            // Same revision, different pixels: the cached answer stands.
            file_put_contents($path, $this->fakeJpegBinary(10, 10));
            touch($path, $mtime);
            $this->assertSame([64, 48], ImageDimensions::of($path));

            // A newer file is a new revision, so it is measured again.
            touch($path, $mtime + 60);
            $this->assertSame([10, 10], ImageDimensions::of($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_dimensions_of_a_missing_file_are_zero(): void
    {
        $this->assertSame([0, 0], ImageDimensions::of('/does/not/exist-' . uniqid() . '.jpg'));
    }
}
