# Internet Guru Laravel Images

Signed, resized and cached images for Laravel applications. Every image on a disk is served through one route at the sizes the application links to, in the best format the browser accepts, and kept on disk once rendered.

| Branch  | Status | Code Coverage |
| :------------- | :------------- | :------------- |
| Main | ![tests](https://github.com/internetguru/laravel-images/actions/workflows/phpunit.yml/badge.svg?branch=main) | ![coverage](https://raw.githubusercontent.com/internetguru/laravel-images/refs/heads/badges/main-coverage.svg) |

## Features

- Signed URLs, so only the sizes the application links to can be requested (HMAC-SHA256, or league/glide-compatible signatures)
- Crop to both dimensions, fit to one, or fit within a default box; never upscaled, never beyond a global cap
- AVIF or WebP for browsers that accept them, PNG for sources with transparency, JPEG otherwise
- A `srcset` with the double-density rendition wherever the source is large enough
- Rendered images cached on disk, keyed by the source's modification time, so a replaced source renders anew
- Browser caching with `ETag`, `Last-Modified` and `304 Not Modified`
- Renditions still in use are rendered again in the background before they age out; unused ones are pruned daily

## Requirements

PHP 8.3+, Laravel 12 or 13, GD or Imagick with AVIF and WebP support, and a queue worker.

## Installation

1. `composer require internetguru/laravel-images`
1. Optionally publish the configuration: `php artisan vendor:publish --tag=ig-images:config`

## Usage

```blade
<x-ig-images::img path="public/images/menu/steak.jpg" width="400" height="300" alt="Rib-eye steak" />
```

- The component renders `src`, `srcset`, `width`, `height`, `alt` and `loading`; any other attribute (`class`, `id`, …) is passed through.
- `alt` is required: describe the image, or leave it empty (`alt=""`) when it is only decoration.
- `width` and `height` size it as `thumbnail()` does; `full` renders it at its own ratio as `fullImage()` does.
- `loading` is `lazy` by default. Give the images seen without scrolling `loading="eager"`, and the main one also `fetchpriority="high"`.

Where the component does not fit, use the helpers and put every value on the tag:

```blade
@php($thumb = thumbnail('public/images/menu/steak.jpg', 400, 300))
<img src="{{ $thumb['url'] }}" srcset="{{ $thumb['srcset'] }}" width="{{ $thumb['width'] }}" height="{{ $thumb['height'] }}" alt="…">
```

- `thumbnail($path, $width = null, $height = null)`: both dimensions crop to their ratio from the centre, one fits that dimension, none fits within `default_size`.
- `fullImage($path)`: the image at its own ratio, held only to `max_size`.
- Both return `url`, the `width` and `height` the image renders at, and `srcset` (null when the source is too small for a sharper rendition). Paths are relative to the source disk.
- `InternetGuru\LaravelImages\Thumbnail` offers the same through `Thumbnail::sized()` and `Thumbnail::full()`.

## Configuration

`config/ig-images.php`:

| Key | Default | Meaning |
| :-- | :-- | :-- |
| `route` | `img` | URL prefix of the image route (`ig-images.show`) |
| `middleware` | `[]` | Middleware of the image route |
| `source_disk` | `null` | Disk with the source images; null is the default disk |
| `cache_disk`, `cache_directory` | `local`, `cache` | Where rendered images are kept |
| `signature` | `hmac` | `hmac` or `glide` |
| `key` | `null` | Signing secret; null is `app.key` |
| `max_size` | `2560` | Square every rendition fits within |
| `default_size` | `800` | Box an image fits within without dimensions |
| `quality` | avif 60, webp 80, jpg 82 | Encoding quality per format |
| `browser_cache_seconds` | `86400` | `max-age` of image responses |
| `renew_after_days` | `20` | Age at which a served rendition is rendered again in the background |
| `prune_after_days` | `30` | Age at which `images:prune-cache` deletes a rendition |
| `prune_at` | `03:00` | Daily time the prune is scheduled; null to schedule it yourself |

### Moving from league/glide

Set `signature` to `glide` and keep `key` at the secret Glide signed with (Glide's `app.key` by default). Links Glide signed stay valid, and its links without a `fit` redirect to the fit they rendered with: `crop` when both dimensions were given, `max` otherwise.

## Cache

- `images:prune-cache` deletes renditions older than `prune_after_days`; `--days=0` clears the whole cache, e.g. after changing the sizing rules.
- A rendition served once it is `renew_after_days` old is served as it is and rendered again by a queued job, so only renditions nobody requested in the meantime are pruned.

## Testing

`./test.sh` builds the Docker image and runs the PHPUnit suite.

## License & Commercial Terms

### License

Copyright © 2026 **Internet Guru**

This software is licensed under the [Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International (CC BY-NC-SA 4.0)](http://creativecommons.org/licenses/by-nc-sa/4.0/) license.

> **Disclaimer:** This software is provided "as is", without warranty of any kind, express or implied. In no event shall the authors or copyright holders be liable for any claim, damages or other liability.

### Commercial Use

The standard CC BY-NC-SA license prohibits commercial use. If you wish to use this software in a commercial environment or product, we offer **flexible commercial licenses** tailored to:

* Your company size.
* The nature of your project.
* Your specific integration needs.

**Note:** In many instances (especially for startups or small-scale tools), this may result in no fees being charged at all. Please contact us to obtain written permission or a commercial agreement.

**Contact for Licensing:** [info@internetguru.io](mailto:info@internetguru.io)

### Professional Services

Are you looking to get the most out of this project? We are available for:

* **Custom Development:** Tailoring the software to your specific requirements.
* **Integration & Support:** Helping your team implement and maintain the solution.
* **Training & Workshops:** Seminars and hands-on workshops for your developers.

Reach out to us at [info@internetguru.io](mailto:info@internetguru.io) — we're happy to help!
