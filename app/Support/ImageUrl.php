<?php

namespace App\Support;

class ImageUrl
{
    public static function forDisplay(string $url): string
    {
        $host = app()->runningInConsole()
            ? parse_url(config('app.url'), PHP_URL_HOST)
            : request()->getHost();

        $parts = parse_url($url);
        if ($parts === false || isset($parts['scheme']) && !in_array($parts['scheme'], ['http', 'https'], true)) {
            return $url;
        }
        if ($url === '' || str_starts_with($url, '#')) {
            return $url;
        }

        $loopbackHosts = ['localhost', '127.0.0.1', '::1', '[::1]'];
        $productionUrl = rtrim(config('app.production_url'), '/');
        $productionHost = parse_url($productionUrl, PHP_URL_HOST);

        if (in_array($host, $loopbackHosts, true)) {
            if (isset($parts['host']) && !in_array($parts['host'], array_merge([$host], $loopbackHosts), true)) {
                return $url;
            }

            return self::withBaseUrl($productionUrl, $parts);
        }

        if (($parts['scheme'] ?? null) !== 'http' || !isset($parts['host'])) {
            return $url;
        }

        $normaliseHost = static fn (?string $value): string => preg_replace('/^www\./i', '', strtolower((string) $value));

        if ($normaliseHost($parts['host']) !== $normaliseHost($host)
            || $normaliseHost($productionHost) !== $normaliseHost($host)
            || parse_url($productionUrl, PHP_URL_SCHEME) !== 'https') {
            return $url;
        }

        return self::withBaseUrl($productionUrl, $parts);
    }

    private static function withBaseUrl(string $baseUrl, array $parts): string
    {
        return $baseUrl.'/'.ltrim($parts['path'] ?? '', '/')
            .(isset($parts['query']) ? '?'.$parts['query'] : '')
            .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
    }
}
