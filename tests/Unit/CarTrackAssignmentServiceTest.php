<?php

namespace Tests\Unit;

use App\Models\CarTrack;
use App\Services\CarTrackAssignmentService;
use Tests\TestCase;

class CarTrackAssignmentServiceTest extends TestCase
{
    public function test_it_assigns_driver_when_exactly_one_usage_matches(): void
    {
        $service = new class extends CarTrackAssignmentService {
            public function decide(?int $vehicleItemId, $passageAt, array $usageMatches): array
            {
                return $this->buildAssignmentDecision($vehicleItemId, $passageAt, $usageMatches);
            }
        };

        $decision = $service->decide(12, '2026-06-06 07:54:15', [
            ['id' => 99, 'driver_id' => 40],
        ]);

        $this->assertSame(CarTrack::STATUS_ASSIGNED, $decision['status']);
        $this->assertSame(40, $decision['driver_id']);
        $this->assertSame(99, $decision['vehicle_usage_id']);
        $this->assertSame([99], $decision['usage_ids']);
    }

    public function test_it_blocks_assignment_when_multiple_usages_match_same_timestamp(): void
    {
        $service = new class extends CarTrackAssignmentService {
            public function decide(?int $vehicleItemId, $passageAt, array $usageMatches): array
            {
                return $this->buildAssignmentDecision($vehicleItemId, $passageAt, $usageMatches);
            }
        };

        $decision = $service->decide(12, '2026-06-06 07:54:15', [
            ['id' => 99, 'driver_id' => 40],
            ['id' => 100, 'driver_id' => 41],
        ]);

        $this->assertSame(CarTrack::STATUS_MULTIPLE_USAGE_MATCHES, $decision['status']);
        $this->assertNull($decision['driver_id']);
        $this->assertNull($decision['vehicle_usage_id']);
        $this->assertSame([99, 100], $decision['usage_ids']);
    }

    public function test_it_does_not_assign_when_passage_has_no_usage_match(): void
    {
        $service = new class extends CarTrackAssignmentService {
            public function decide(?int $vehicleItemId, $passageAt, array $usageMatches): array
            {
                return $this->buildAssignmentDecision($vehicleItemId, $passageAt, $usageMatches);
            }
        };

        $decision = $service->decide(12, '2026-06-06 07:54:15', []);

        $this->assertSame(CarTrack::STATUS_NO_USAGE_MATCH, $decision['status']);
        $this->assertNull($decision['driver_id']);
        $this->assertNull($decision['vehicle_usage_id']);
    }

    public function test_it_does_not_assign_without_timestamp(): void
    {
        $service = new class extends CarTrackAssignmentService {
            public function decide(?int $vehicleItemId, $passageAt, array $usageMatches): array
            {
                return $this->buildAssignmentDecision($vehicleItemId, $passageAt, $usageMatches);
            }
        };

        $decision = $service->decide(12, null, []);

        $this->assertSame(CarTrack::STATUS_MISSING_TIMESTAMP, $decision['status']);
        $this->assertNull($decision['driver_id']);
    }

    public function test_it_does_not_assign_without_vehicle_match(): void
    {
        $service = new class extends CarTrackAssignmentService {
            public function decide(?int $vehicleItemId, $passageAt, array $usageMatches): array
            {
                return $this->buildAssignmentDecision($vehicleItemId, $passageAt, $usageMatches);
            }
        };

        $decision = $service->decide(null, '2026-06-06 07:54:15', []);

        $this->assertSame(CarTrack::STATUS_VEHICLE_NOT_FOUND, $decision['status']);
        $this->assertNull($decision['driver_id']);
    }
}
