<?php

namespace Tests\Feature;

use InternetGuru\LaravelImages\Thumbnail;
use Tests\TestCase;

class UrlSignerTest extends TestCase
{
    public function test_glide_mode_signs_exactly_as_league_glide_did(): void
    {
        config(['ig-images.signature' => 'glide', 'ig-images.key' => 'base64:test-key']);

        $this->assertSame(
            '/img/public/images/all/kachna s zelím.jpg?w=200&h=150&fit=crop&s=bf426d7a5ed80d06e778615d178c2076',
            Thumbnail::sized(200, 150)->url('public/images/all/kachna s zelím.jpg')
        );
    }

    public function test_hmac_mode_signs_with_sha256(): void
    {
        config(['ig-images.signature' => 'hmac', 'ig-images.key' => 'base64:test-key']);

        $expected = hash_hmac('sha256', 'img/a.jpg?fit=max&w=40', 'base64:test-key');

        $this->assertSame("/img/a.jpg?w=40&fit=max&s={$expected}", Thumbnail::sized(40)->url('a.jpg'));
    }

    public function test_the_signature_depends_on_the_key(): void
    {
        config(['ig-images.key' => 'one']);
        $first = Thumbnail::sized(40)->url('a.jpg');
        config(['ig-images.key' => 'two']);

        $this->assertNotSame($first, Thumbnail::sized(40)->url('a.jpg'));
    }
}
