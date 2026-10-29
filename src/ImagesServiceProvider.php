<?php

namespace InternetGuru\LaravelImages;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use InternetGuru\LaravelImages\Console\PruneImageCache;
use InternetGuru\LaravelImages\Http\Controllers\ImageController;

class ImagesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ig-images.php', 'ig-images');
    }

    public function boot(): void
    {
        Route::get(trim(config('ig-images.route'), '/') . '/{path}', [ImageController::class, 'show'])
            ->where('path', '.*')
            ->middleware(config('ig-images.middleware'))
            ->name('ig-images.show');

        $this->publishes([__DIR__ . '/../config/ig-images.php' => config_path('ig-images.php')], 'ig-images:config');

        if ($this->app->runningInConsole()) {
            $this->commands([PruneImageCache::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $pruneAt = config('ig-images.prune_at');
            if ($pruneAt !== null) {
                $schedule->command('images:prune-cache')->dailyAt($pruneAt);
            }
        });
    }

    public static function sourceDisk(): Filesystem
    {
        return Storage::disk(config('ig-images.source_disk'));
    }

    public static function cacheDisk(): Filesystem
    {
        return Storage::disk(config('ig-images.cache_disk'));
    }
}
