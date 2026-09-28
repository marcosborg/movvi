<?php

namespace Tests\Unit;

use App\Services\WeeklyDriverChargePolicy;
use PHPUnit\Framework\TestCase;

class WeeklyDriverChargePolicyTest extends TestCase
{
    public function test_only_agreed_commissions_have_company_paid_charging_from_week_38(): void
    {
        $policy = new WeeklyDriverChargePolicy;
        foreach ([50.0, 55.0, 60.0] as $rate) {
            $this->assertSame(123.45, $policy->companyPaidCharging(1, '2026-09-14', $rate, 123.45));
            $this->assertSame(0.0, $policy->companyPaidCharging(1, '2026-09-07', $rate, 123.45));
        }
        foreach ([0.0, 40.0, 45.0, 49.5] as $rate) {
            $this->assertSame(0.0, $policy->companyPaidCharging(1, '2026-09-14', $rate, 123.45));
        }
        $this->assertSame(0.0, $policy->companyPaidCharging(2, '2026-09-14', 60.0, 123.45));
        $this->assertSame(0.0, $policy->companyPaidCharging(1, '2026-09-14', 60.0, -2));
    }

    public function test_prio_transactions_are_charging_even_when_the_card_type_is_missing(): void
    {
        $policy = new WeeklyDriverChargePolicy;

        $this->assertTrue($policy->isElectricTransaction('prio', 'L'));
        $this->assertTrue($policy->isElectricTransaction('repsol', 'kWh'));
        $this->assertFalse($policy->isElectricTransaction('prio_combustao', 'L'));
        $this->assertFalse($policy->isElectricTransaction('repsol', 'L'));
    }
}
