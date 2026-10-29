<?php

namespace InternetGuru\LaravelImages;

use InvalidArgumentException;

/**
 * Signs image URLs so only the renditions the application links to can be requested.
 * The glide method reproduces league/glide's signature, so links it signed stay valid.
 */
class UrlSigner
{
    public const PARAMETER = 's';

    /** @param  array<string, mixed>  $parameters */
    public function signature(string $path, array $parameters): string
    {
        unset($parameters[self::PARAMETER]);
        ksort($parameters);
        $message = ltrim($path, '/') . '?' . http_build_query($parameters);
        $key = $this->key();

        return match (config('ig-images.signature')) {
            'hmac' => hash_hmac('sha256', $message, $key),
            'glide' => md5($key . ':' . $message),
            default => throw new InvalidArgumentException('ig-images.signature must be hmac or glide.'),
        };
    }

    /** @param  array<string, mixed>  $parameters */
    public function isValid(string $path, array $parameters): bool
    {
        $given = $parameters[self::PARAMETER] ?? null;

        return is_string($given) && hash_equals($this->signature($path, $parameters), $given);
    }

    private function key(): string
    {
        $key = config('ig-images.key') ?? config('app.key');
        if (! is_string($key) || $key === '') {
            throw new InvalidArgumentException('Image URLs need ig-images.key or app.key to sign with.');
        }

        return $key;
    }
}
