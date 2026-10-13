<?php

namespace Tests\Unit;

use App\Services\VehicleProfitabilityPeriodService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class VehicleProfitabilityPeriodServiceTest extends TestCase
{
    public function test_cross_month_rounding_preserves_every_cent(): void
    {
        $method = new ReflectionMethod(VehicleProfitabilityPeriodService::class, 'apportionCents');
        $method->setAccessible(true);
        $service = new VehicleProfitabilityPeriodService();
        $weekStart = Carbon::parse('2026-08-28')->startOfDay();
        $weekEnd = Carbon::parse('2026-09-03')->endOfDay();

        $august = $method->invoke($service, 0.01, 4 / 7, $weekStart, $weekEnd, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31')->endOfDay());
        $september = $method->invoke($service, 0.01, 3 / 7, $weekStart, $weekEnd, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-30')->endOfDay());

        $this->assertSame(0.01, round($august + $september, 2));
    }
}
