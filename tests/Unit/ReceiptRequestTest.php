<?php

namespace Tests\Unit;

use App\Http\Requests\StoreReceiptRequest;
use App\Http\Requests\UpdateReceiptRequest;
use Tests\TestCase;

class ReceiptRequestTest extends TestCase
{
    public function test_store_request_requires_driver_week_and_duplicate_guard(): void
    {
        $rules = (new StoreReceiptRequest())->rules();

        $this->assertContains('exists:drivers,id', $rules['driver_id']);
        $this->assertContains('exists:tvde_weeks,id', $rules['tvde_week_id']);
        $this->assertCount(4, $rules['tvde_week_id']);
    }

    public function test_update_request_requires_driver_week_and_duplicate_guard(): void
    {
        $rules = (new UpdateReceiptRequest())->rules();

        $this->assertContains('exists:drivers,id', $rules['driver_id']);
        $this->assertContains('exists:tvde_weeks,id', $rules['tvde_week_id']);
        $this->assertCount(4, $rules['tvde_week_id']);
    }
}
