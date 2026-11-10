<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\MobileController;
use ReflectionMethod;
use Tests\TestCase;

class MobileAccountAdjustmentSerializationTest extends TestCase
{
    public function test_it_exposes_named_signed_adjustments_for_the_mobile_app(): void
    {
        $method = new ReflectionMethod(MobileController::class, 'serializeAccountAdjustments');
        $method->setAccessible(true);

        $result = $method->invoke(new MobileController(), [
            'adjustments_array' => [
                ['id' => 10, 'name' => 'Lavagem', 'type' => 'deduct', 'amount' => '10.00', 'category' => 'general'],
                ['id' => 11, 'name' => 'Prémio', 'type' => 'refund', 'amount' => 15, 'category' => 'ajuste_manual'],
            ],
        ]);

        $this->assertSame('Lavagem', $result[0]['name']);
        $this->assertSame(-10.0, $result[0]['signed_amount']);
        $this->assertSame('Prémio', $result[1]['name']);
        $this->assertSame(15.0, $result[1]['signed_amount']);
    }
}
