<?php

namespace App\Support;

class ImageUrl
{
    public static function forDisplay(string $url): string
    {
        $host = app()->runningInConsole()
            ? parse_url(config('app.url'), PHP_URL_HOST)
            : request()->getHost();

        if (!in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)) {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || isset($parts['scheme']) && !in_array($parts['scheme'], ['http', 'https'], true)) {
            return $url;
        }
        if (isset($parts['host']) && !in_array($parts['host'], [$host, 'localhost', '127.0.0.1', '::1', '[::1]'], true)) {
            return $url;
        }
        if ($url === '' || str_starts_with($url, '#')) {
            return $url;
        }

        return rtrim(config('app.production_url'), '/').'/'.ltrim($parts['path'] ?? '', '/')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
