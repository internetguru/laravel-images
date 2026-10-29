# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Added

- Images on a disk served through one signed route, resized to the size the application links to, in AVIF or WebP where the browser accepts it, and cached on disk.
- `thumbnail()` and `fullImage()` return the signed URL, the rendered size and a double-density `srcset`.
- Renditions still in use are rendered again in the background, and `images:prune-cache` deletes unused ones daily.
- Links are signed with HMAC-SHA256, or compatibly with league/glide for sites moving from it.

## [0.0.0] - 2026-10-29

### Added

- New changelog file.

[Unreleased]: git diff staging..dev
[0.0.0]: git log v0.0.0
