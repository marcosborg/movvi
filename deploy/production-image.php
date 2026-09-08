<?php
// Install in public/. Keep the configuration outside the web root.
declare(strict_types=1);

header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
$fail = static function (int $status): void {
    http_response_code($status);
    exit;
};
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    $fail(405);
}
$configPath = dirname(__DIR__, 2).'/.movvi-image-reader.json';
$config = is_file($configPath) ? json_decode((string) file_get_contents($configPath), true) : null;
$token = $_SERVER['HTTP_X_MOVVI_IMAGE_KEY'] ?? '';
if (!is_array($config) || empty($config['token_hash']) || strlen($token) !== 64
    || !hash_equals($config['token_hash'], hash('sha256', $token))
    || ($config['expires_at'] ?? 0) < time()) {
    $fail(403);
}
$path = $_GET['path'] ?? '';
if (!is_string($path) || !preg_match('~\Asupport-tickets/[1-9][0-9]*/[a-f0-9-]{36}\.(jpg|jpeg|png|webp)\z~i', $path)) {
    $fail(404);
}
$root = realpath(dirname(__DIR__).'/storage/app/support-tickets');
$file = realpath(dirname(__DIR__).'/storage/app/'.$path);
if (!$root || !$file || !str_starts_with($file, $root.DIRECTORY_SEPARATOR) || !is_file($file)) {
    $fail(404);
}
$size = filesize($file);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file);
if ($size === false || $size > 8 * 1024 * 1024 || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
    $fail(415);
}
header('Content-Type: '.$mime);
header('Content-Length: '.$size);
header('Content-Disposition: inline');
readfile($file);
