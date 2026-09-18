<?php

namespace Tests\Unit;

use App\Services\WeeklyExcessKilometerPolicy;
use PHPUnit\Framework\TestCase;

class WeeklyExcessKilometerPolicyTest extends TestCase
{
    public function test_it_charges_only_excess_kilometers_for_cession_contracts_from_week_38(): void
    {
        $policy = new WeeklyExcessKilometerPolicy;

        $result = $policy->calculate(1, '2026-09-14', 0, 2150, 'Motorista normal');
        $this->assertTrue($result['eligible']);
        $this->assertSame(150.0, $result['kilometers']);
        $this->assertSame(15.0, $result['charge']);

        $exception = $policy->calculate(1, '2026-09-14', 0, 2300, 'Celso Cristiano');
        $this->assertSame(100.0, $exception['kilometers']);
        $this->assertSame(5.0, $exception['charge']);

        $this->assertSame(0.0, $policy->calculate(1, '2026-09-07', 0, 2300)['charge']);
        $this->assertSame(0.0, $policy->calculate(1, '2026-09-14', 50, 2300)['charge']);
        $this->assertSame(0.0, $policy->calculate(2, '2026-09-14', 0, 2300)['charge']);
    }
}
