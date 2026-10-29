# Laravel Images (internetguru/laravel-images)

Signed, resized and cached images served through one route. The full reference is `vendor/internetguru/laravel-images/README.md`.

## Usage

- Link an image with `thumbnail($path, $width, $height)` (both dimensions crop, one fits, none fits within `default_size`) or `fullImage($path)` (own ratio, held to `max_size`). Paths are relative to the source disk. Both return `url`, `width`, `height` and `srcset`; put all of them on the `<img>` so the layout does not shift and high-resolution screens get the sharper rendition.
- Never build image URLs by hand: they carry a signature, and an unsigned or altered URL answers 403.
- Settings live in `config/ig-images.php`. A site moved from league/glide sets `signature` to `glide` so its old links keep working.

## Cache

- Rendered images live in `cache_directory` on `cache_disk`. A replaced source renders anew by itself. After changing sizing or quality, clear the cache with `php artisan images:prune-cache --days=0`.
- An ageing rendition is renewed by a queued job, so a queue worker must run. `images:prune-cache` is scheduled daily at `prune_at`.

## Tests

- Fake the disks with `Storage::fake()` and request `thumbnail(...)['url']`; fake the queue when asserting on `RenewImageRendition`.
