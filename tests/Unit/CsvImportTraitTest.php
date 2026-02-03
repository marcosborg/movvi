<?php

namespace Tests\Unit;

use App\Http\Controllers\Traits\CsvImportTrait;
use Tests\TestCase;

class CsvImportTraitTest extends TestCase
{
    public function test_it_normalizes_combustion_created_at_and_backfills_updated_at(): void
    {
        $harness = new class {
            use CsvImportTrait;

            public function normalize(string $modelName, array $row): array
            {
                return $this->normalizeImportRow($modelName, $row);
            }
        };

        $row = $harness->normalize('CombustionTransaction', [
            'tvde_week_id' => 1,
            'card' => 'ABC123',
            'amount' => '10',
            'total' => '20.50',
            'created_at' => '31/01/2026 10:20',
        ]);

        $this->assertSame('2026-01-31 10:20:00', $row['created_at']);
        $this->assertSame('2026-01-31 10:20:00', $row['updated_at']);
    }

    public function test_it_normalizes_combustion_date_field_when_present(): void
    {
        $harness = new class {
            use CsvImportTrait;

            public function normalize(string $modelName, array $row): array
            {
                return $this->normalizeImportRow($modelName, $row);
            }
        };

        $row = $harness->normalize('CombustionTransaction', [
            'tvde_week_id' => 1,
            'card' => 'ABC123',
            'amount' => '10',
            'total' => '20.50',
            'date' => '31/01/2026 10:20',
        ]);

        $this->assertSame('2026-01-31 10:20:00', $row['date']);
    }
}
