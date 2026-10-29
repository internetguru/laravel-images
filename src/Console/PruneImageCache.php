<?php

namespace InternetGuru\LaravelImages\Console;

use Illuminate\Console\Command;
use InternetGuru\LaravelImages\ImagesServiceProvider;

class PruneImageCache extends Command
{
    protected $signature = 'images:prune-cache {--days= : Delete renditions rendered more than this many days ago, ig-images.prune_after_days by default}';

    protected $description = 'Delete old rendered images; the ones still in use are rendered again on their next request';

    public function handle(): int
    {
        $disk = ImagesServiceProvider::cacheDisk();
        $days = $this->option('days') ?? config('ig-images.prune_after_days');
        $threshold = now()->subDays((int) $days)->getTimestamp();

        $stale = array_filter(
            $disk->files(config('ig-images.cache_directory')),
            fn (string $file) => $disk->lastModified($file) <= $threshold
        );
        $disk->delete($stale);

        $this->info('Deleted ' . count($stale) . ' rendered images.');

        return self::SUCCESS;
    }
}
