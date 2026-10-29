<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneImageCacheCommandTest extends TestCase
{
    public function test_deletes_renditions_older_than_thirty_days_and_keeps_the_rest(): void
    {
        $disk = Storage::fake('local');
        $disk->put('cache/old.avif', 'old');
        $disk->put('cache/fresh.avif', 'fresh');
        touch($disk->path('cache/old.avif'), now()->subDays(31)->getTimestamp());

        $this->artisan('images:prune-cache')->expectsOutput('Deleted 1 rendered images.')->assertSuccessful();

        $disk->assertMissing('cache/old.avif');
        $disk->assertExists('cache/fresh.avif');
    }

    public function test_zero_days_clears_the_whole_cache_and_nothing_else(): void
    {
        $disk = Storage::fake('local');
        $disk->put('cache/fresh.avif', 'fresh');
        $disk->put('public/images/all/source.jpg', 'source');

        $this->artisan('images:prune-cache', ['--days' => 0])->assertSuccessful();

        $disk->assertMissing('cache/fresh.avif');
        $disk->assertExists('public/images/all/source.jpg');
    }

    public function test_runs_daily_at_the_configured_time(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'images:prune-cache'));

        $this->assertSame('0 3 * * *', $event?->expression);
    }
}
