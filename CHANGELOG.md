# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [0.2.0-rc.1] - 2026-10-30

### Added

- `<x-ig-images::img>` renders an image from the source disk with its size, `srcset`, a required `alt` and `loading="lazy"` by default.

## [0.1.0] - 2026-10-29

_Stable release based on [0.1.0-rc.1]._

## [0.1.0-rc.1] - 2026-10-29

### Added

- Images on a disk served through one signed route, resized to the size the application links to, in AVIF or WebP where the browser accepts it, and cached on disk.
- `thumbnail()` and `fullImage()` return the signed URL, the rendered size and a double-density `srcset`.
- Renditions still in use are rendered again in the background, and `images:prune-cache` deletes unused ones daily.
- Links are signed with HMAC-SHA256, or compatibly with league/glide for sites moving from it.

## [0.0.0] - 2026-10-29

### Added

- New changelog file.

[0.2.0-rc.1]: https://github.com/internetguru/laravel-images/releases/tag/v0.1.0
[0.1.0]: https://github.com/internetguru/laravel-images/compare/v0.0.0...v0.1.0
[0.1.0-rc.1]: https://github.com/internetguru/laravel-images/releases/tag/v0.0.0
[0.0.0]: git log v0.0.0
