<?php

namespace Tests\Unit;

use App\Support\ImageUrl;
use Tests\TestCase;

class ImageUrlTest extends TestCase
{
    public function test_local_images_use_production_without_changing_external_or_embedded_images(): void
    {
        config(['app.url' => 'http://127.0.0.1:8000', 'app.production_url' => 'https://movvi.com.pt']);
        $this->assertSame('https://movvi.com.pt/storage/a.jpg?v=2', ImageUrl::forDisplay('http://127.0.0.1:8000/storage/a.jpg?v=2'));
        $this->assertSame('https://movvi.com.pt/admin/support-ticket-attachments/2', ImageUrl::forDisplay('/admin/support-ticket-attachments/2'));
        $this->assertSame('https://other.test/a.jpg', ImageUrl::forDisplay('https://other.test/a.jpg'));
        $this->assertSame('data:image/png;base64,abc', ImageUrl::forDisplay('data:image/png;base64,abc'));
        $this->assertSame('', ImageUrl::forDisplay(''));
    }

    public function test_non_loopback_environments_keep_their_image_urls(): void
    {
        foreach (['https://movvi.com.pt', 'https://staging.movvi.com.pt'] as $url) {
            config(['app.url' => $url]);
            $this->assertSame('/storage/a.jpg', ImageUrl::forDisplay('/storage/a.jpg'));
        }
    }

    public function test_localhost_and_ipv6_use_the_same_rule(): void
    {
        foreach (['http://localhost:8000', 'http://[::1]:8000'] as $url) {
            config(['app.url' => $url, 'app.production_url' => 'https://movvi.com.pt']);
            $this->assertSame('https://movvi.com.pt/storage/a.jpg', ImageUrl::forDisplay($url.'/storage/a.jpg'));
        }
    }
}
