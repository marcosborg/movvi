<?php

namespace Tests\Unit;

use App\Services\CombustionTransactionAssignmentService;
use Tests\TestCase;

class CombustionTransactionAssignmentServiceTest extends TestCase
{
    public function test_it_assigns_driver_when_exactly_one_usage_matches(): void
    {
        $service = new class extends CombustionTransactionAssignmentService {
            public function decide(bool $hasCard, ?int $vehicleItemId, $transactionAt, array $usageMatches, ?int $legacyDriverId): array
            {
                return $this->buildAssignmentDecision($hasCard, $vehicleItemId, $transactionAt, $usageMatches, $legacyDriverId);
            }
        };

        $decision = $service->decide(true, 12, '2026-04-11 01:19:47', [
            ['id' => 99, 'driver_id' => 40],
        ], 7);

        $this->assertSame('assigned', $decision['status']);
        $this->assertSame(40, $decision['driver_id']);
        $this->assertSame([99], $decision['usage_ids']);
    }

    public function test_it_blocks_assignment_when_multiple_usages_match_same_timestamp(): void
    {
        $service = new class extends CombustionTransactionAssignmentService {
            public function decide(bool $hasCard, ?int $vehicleItemId, $transactionAt, array $usageMatches, ?int $legacyDriverId): array
            {
                return $this->buildAssignmentDecision($hasCard, $vehicleItemId, $transactionAt, $usageMatches, $legacyDriverId);
            }
        };

        $decision = $service->decide(true, 12, '2026-04-11 01:19:47', [
            ['id' => 99, 'driver_id' => 40],
            ['id' => 100, 'driver_id' => 41],
        ], 7);

        $this->assertSame('multiple_usage_matches', $decision['status']);
        $this->assertNull($decision['driver_id']);
        $this->assertSame([99, 100], $decision['usage_ids']);
    }

    public function test_it_does_not_fallback_to_legacy_driver_when_timestamp_has_no_usage_match(): void
    {
        $service = new class extends CombustionTransactionAssignmentService {
            public function decide(bool $hasCard, ?int $vehicleItemId, $transactionAt, array $usageMatches, ?int $legacyDriverId): array
            {
                return $this->buildAssignmentDecision($hasCard, $vehicleItemId, $transactionAt, $usageMatches, $legacyDriverId);
            }
        };

        $decision = $service->decide(true, 12, '2026-04-11 01:19:47', [], 7);

        $this->assertSame('no_usage_match', $decision['status']);
        $this->assertNull($decision['driver_id']);
    }

    public function test_it_uses_legacy_driver_only_when_transaction_has_no_timestamp(): void
    {
        $service = new class extends CombustionTransactionAssignmentService {
            public function decide(bool $hasCard, ?int $vehicleItemId, $transactionAt, array $usageMatches, ?int $legacyDriverId): array
            {
                return $this->buildAssignmentDecision($hasCard, $vehicleItemId, $transactionAt, $usageMatches, $legacyDriverId);
            }
        };

        $decision = $service->decide(true, 12, null, [], 7);

        $this->assertSame('legacy_fallback', $decision['status']);
        $this->assertSame(7, $decision['driver_id']);
    }
}
