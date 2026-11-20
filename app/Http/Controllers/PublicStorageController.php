<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicStorageController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        $root = realpath(storage_path('app/public'));
        $file = $root === false
            ? false
            : realpath($root.DIRECTORY_SEPARATOR.ltrim($path, '/'));

        abort_if(
            $root === false
            || $file === false
            || !str_starts_with($file, $root.DIRECTORY_SEPARATOR)
            || !is_file($file),
            404
        );

        return response()->file($file, [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
