<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ProductionTicketImage
{
    public function response(string $path)
    {
        $token = config('support.production_image_key');
        abort_unless(is_string($token) && strlen($token) === 64, 503, 'Leitura de imagens de produção ainda não configurada.');
        abort_unless(preg_match('~\Asupport-tickets/[1-9][0-9]*/[a-f0-9-]{36}\.(jpg|jpeg|png|webp)\z~i', $path), 404);
        $base = rtrim(config('app.production_url'), '/');
        abort_unless(parse_url($base, PHP_URL_SCHEME) === 'https', 503);
        $options = [];
        $ip = config('support.production_image_ip');
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $host = parse_url($base, PHP_URL_HOST);
            $port = parse_url($base, PHP_URL_PORT) ?: 443;
            $options['curl'][CURLOPT_RESOLVE] = ["{$host}:{$port}:{$ip}"];
        }
        try {
            $remote = Http::withHeaders(['X-Movvi-Image-Key' => $token])
                ->withOptions($options)
                ->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->get($base.'/production-image.php', ['path' => $path]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            abort(502, 'Não foi possível carregar a imagem de produção.');
        }
        abort_unless($remote->successful(), $remote->status() === 404 ? 404 : 502);
        $mime = trim(explode(';', $remote->header('Content-Type'))[0]);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
            && strlen($remote->body()) <= 8 * 1024 * 1024, 502);

        return response($remote->body(), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
