<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use InternetGuru\LaravelImages\Jobs\RenewImageRendition;
use InternetGuru\LaravelImages\Thumbnail;
use Tests\TestCase;

class RenewImageRenditionTest extends TestCase
{
    public function test_renders_the_rendition_again_and_refreshes_its_age(): void
    {
        $disk = Storage::fake('local');
        $disk->put('source.jpg', $this->fakeJpegBinary(100, 50));
        $disk->put('cache/rendition.jpg', 'old-bytes');
        touch($disk->path('cache/rendition.jpg'), now()->subDays(21)->getTimestamp());

        RenewImageRendition::dispatchSync($disk->path('source.jpg'), Thumbnail::sized(40), 'jpg', 'cache/rendition.jpg');

        $this->assertSame([40, 20], $this->renderedSize($disk->get('cache/rendition.jpg')));
        $this->assertGreaterThan(now()->subMinute()->getTimestamp(), $disk->lastModified('cache/rendition.jpg'));
        $this->assertSame(['cache/rendition.jpg'], $disk->files('cache'));
    }

    public function test_leaves_the_rendition_alone_when_the_source_is_gone(): void
    {
        $disk = Storage::fake('local');
        $disk->put('cache/rendition.jpg', 'old-bytes');

        RenewImageRendition::dispatchSync($disk->path('missing.jpg'), Thumbnail::sized(40), 'jpg', 'cache/rendition.jpg');

        $this->assertSame('old-bytes', $disk->get('cache/rendition.jpg'));
    }
}
