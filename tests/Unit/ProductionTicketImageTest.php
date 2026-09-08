<?php
namespace Tests\Unit;

use App\Services\ProductionTicketImage;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProductionTicketImageTest extends TestCase
{
    private string $path = 'support-tickets/3/a6743c43-a594-4300-981a-ec60d1e9a73c.jpg';

    protected function setUp(): void
    {
        parent::setUp();
        config(['support.production_image_key' => str_repeat('a', 64), 'app.production_url' => 'https://movvi.com.pt']);
        Http::preventStrayRequests();
    }

    public function test_proxies_image_without_exposing_key_to_browser(): void
    {
        Http::fake(['movvi.com.pt/*' => Http::response('image-bytes', 200, ['Content-Type' => 'image/jpeg'])]);
        $response = app(ProductionTicketImage::class)->response($this->path);
        $this->assertSame('image-bytes', $response->getContent());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertFalse($response->headers->has('X-Movvi-Image-Key'));
        Http::assertSent(fn ($r) => $r->hasHeader('X-Movvi-Image-Key', str_repeat('a', 64)) && $r['path'] === $this->path);
    }

    public function test_rejects_login_html_instead_of_serving_it_as_image(): void
    {
        Http::fake(['*' => Http::response('<html>Login</html>', 200, ['Content-Type' => 'text/html'])]);
        $this->expectException(HttpException::class);
        app(ProductionTicketImage::class)->response($this->path);
    }

    public function test_rejects_paths_outside_ticket_images_without_network_request(): void
    {
        try {
            app(ProductionTicketImage::class)->response('../.env');
            $this->fail('Expected rejection');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
            Http::assertNothingSent();
        }
    }
}
