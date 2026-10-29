<?php

return [
    // URL prefix the images are served under, e.g. /img/photos/a.jpg?w=200&fit=max&s=…
    'route' => 'img',

    // Middleware for the image route; images need no session, so none by default.
    'middleware' => [],

    // Disk holding the source images; null is the default disk.
    'source_disk' => null,

    // Disk and folder holding rendered images.
    'cache_disk' => 'local',
    'cache_directory' => 'cache',

    // 'hmac' (HMAC-SHA256) or 'glide', which accepts links signed by league/glide and redirects
    // its links without a fit to the fit they used to render with.
    'signature' => 'hmac',

    // Secret the links are signed with; null is app.key.
    'key' => null,

    // Every rendered image fits within this square, whatever the request asks for.
    'max_size' => 2560,

    // Box an image fits within when neither width nor height is given.
    'default_size' => 800,

    // Encoding quality per output format.
    'quality' => ['avif' => 60, 'webp' => 80, 'jpg' => 82],

    // How long a browser may reuse a rendered image before revalidating it.
    'browser_cache_seconds' => 86400,

    // A rendered image served at this age is rendered again in the background; keep it below prune_after_days.
    'renew_after_days' => 20,

    // images:prune-cache deletes rendered images older than this.
    'prune_after_days' => 30,

    // Daily time images:prune-cache is scheduled at; null leaves scheduling to the application.
    'prune_at' => '03:00',
];
