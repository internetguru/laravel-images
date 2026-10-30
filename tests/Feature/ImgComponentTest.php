<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImgComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local')->put('photo.jpg', $this->fakeJpegBinary(1000, 500));
    }

    public function test_renders_the_size_srcset_and_alt_of_a_thumbnail(): void
    {
        $html = Blade::render('<x-ig-images::img path="photo.jpg" width="200" alt="A photo" class="rounded" />');

        $this->assertStringContainsString('width="200"', $html);
        $this->assertStringContainsString('height="100"', $html);
        $this->assertStringContainsString('srcset="', $html);
        $this->assertStringContainsString('alt="A photo"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('class="rounded"', $html);
        $this->assertStringNotContainsString('fetchpriority', $html);
    }

    public function test_keeps_an_empty_alt_for_decoration(): void
    {
        $html = Blade::render('<x-ig-images::img path="photo.jpg" alt="" />');

        $this->assertStringContainsString('alt=""', $html);
    }

    public function test_an_image_seen_without_scrolling_loads_eagerly_with_priority(): void
    {
        $html = Blade::render('<x-ig-images::img path="photo.jpg" alt="" loading="eager" fetchpriority="high" />');

        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);
    }

    public function test_full_image_keeps_its_own_size(): void
    {
        $html = Blade::render('<x-ig-images::img path="photo.jpg" alt="" full />');

        $this->assertStringContainsString('width="1000"', $html);
        $this->assertStringContainsString('height="500"', $html);
        $this->assertStringContainsString('fit=max', $html);
    }
}
