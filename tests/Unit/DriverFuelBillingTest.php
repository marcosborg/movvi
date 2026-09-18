<?php

namespace Tests\Unit;

use App\Models\Driver;
use PHPUnit\Framework\TestCase;

class DriverFuelBillingTest extends TestCase
{
    public function test_fuel_is_billable_when_driver_pays_fuel(): void
    {
        $driver = new Driver(['pays_fuel' => true]);

        $this->assertSame(42.5, $driver->billableFuelAmount(42.5));
    }

    public function test_fuel_is_not_billable_when_driver_does_not_pay_fuel(): void
    {
        $driver = new Driver(['pays_fuel' => false]);

        $this->assertSame(0.0, $driver->billableFuelAmount(42.5));
    }
}
