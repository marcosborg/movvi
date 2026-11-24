<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\MobileController;
use App\Models\ContractVat;
use App\Models\Driver;
use App\Models\DriversBalance;
use ReflectionMethod;
use Tests\TestCase;

class MobileBalanceSerializationTest extends TestCase
{
    public function test_cleared_balance_does_not_expose_a_tax_only_receipt_value(): void
    {
        $result = $this->serializeBalance(209.32, 0.0);

        $this->assertSame(12.56, $result['vat']);
        $this->assertSame(0.0, $result['final']);
    }

    public function test_open_balance_keeps_the_configured_tax_calculation(): void
    {
        $result = $this->serializeBalance(209.32, 209.32);

        $this->assertSame(221.88, $result['final']);
    }

    private function serializeBalance(float $value, float $newBalance): array
    {
        $driver = new Driver();
        $driver->setRelation('contract_vat', new ContractVat([
            'iva' => 6,
            'rf' => 0,
        ]));

        $balance = new DriversBalance([
            'value' => $value,
            'last_balance' => 0,
            'new_balance' => $newBalance,
        ]);

        $method = new ReflectionMethod(MobileController::class, 'serializeBalance');
        $method->setAccessible(true);

        return $method->invoke(new MobileController(), $balance, $driver);
    }
}
