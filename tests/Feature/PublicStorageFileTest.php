<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageFileTest extends TestCase
{
    public function test_public_storage_files_are_served_by_the_application(): void
    {
        $path = 'test-public-storage/image.txt';
        Storage::disk('public')->put($path, 'movvi-image');

        try {
            $this->get('/storage/'.$path)
                ->assertOk()
                ->assertHeader('cache-control', 'immutable, max-age=604800, public')
                ->assertHeader('content-type', 'text/plain; charset=UTF-8');
        } finally {
            Storage::disk('public')->delete($path);
            Storage::disk('public')->deleteDirectory('test-public-storage');
        }
    }

    public function test_public_storage_route_rejects_missing_files(): void
    {
        $this->get('/storage/missing/file.jpg')->assertNotFound();
    }
}
