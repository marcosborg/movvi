<?php

namespace Tests\Unit;

use App\Http\Requests\StoreAdjustmentRequest;
use App\Http\Requests\UpdateAdjustmentRequest;
use Tests\TestCase;

class AdjustmentRequestTest extends TestCase
{
    public function test_store_request_includes_amount_rule(): void
    {
        $rules = (new StoreAdjustmentRequest())->rules();

        $this->assertArrayHasKey('amount', $rules);
        $this->assertSame(['numeric', 'nullable'], $rules['amount']);
    }

    public function test_update_request_includes_amount_rule(): void
    {
        $rules = (new UpdateAdjustmentRequest())->rules();

        $this->assertArrayHasKey('amount', $rules);
        $this->assertSame(['numeric', 'nullable'], $rules['amount']);
    }
}
