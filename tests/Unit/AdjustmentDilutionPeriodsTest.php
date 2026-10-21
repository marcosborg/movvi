<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\AdjustmentController;
use Tests\TestCase;

class AdjustmentDilutionPeriodsTest extends TestCase
{
    public function test_future_installments_do_not_depend_on_precreated_tvde_weeks(): void
    {
        $controller = new class extends AdjustmentController {
            public function periods(string $startDate, int $weeks): array
            {
                return $this->dilutionPeriods($startDate, $weeks);
            }
        };

        $periods = $controller->periods('2026-09-28', 5);

        $this->assertCount(5, $periods);
        $this->assertSame('2026-09-28', $periods[0]['start']->toDateString());
        $this->assertSame('2026-10-04', $periods[0]['end']->toDateString());
        $this->assertSame('2026-10-26', $periods[4]['start']->toDateString());
        $this->assertSame('2026-11-01', $periods[4]['end']->toDateString());
    }
}
