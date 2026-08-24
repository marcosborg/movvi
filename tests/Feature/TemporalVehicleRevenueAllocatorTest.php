<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\TvdeActivityEntry;
use App\Models\TvdeOperator;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
use App\Models\VehicleModel;
use App\Models\VehicleUsage;
use App\Services\TemporalVehicleRevenueAllocator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TemporalVehicleRevenueAllocatorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_assigns_an_entry_to_the_only_operational_vehicle_in_use(): void
    {
        [$entry, $operational, $service] = $this->scenario();

        VehicleUsage::create([
            'driver_id' => $entry->driver_id,
            'vehicle_item_id' => $operational->id,
            'start_date' => '2035-01-06 08:00:00',
            'end_date' => '2035-01-06 12:00:00',
            'usage_exceptions' => 'usage',
        ]);
        VehicleUsage::create([
            'driver_id' => $entry->driver_id,
            'vehicle_item_id' => $service->id,
            'start_date' => '2035-01-01 00:00:00',
            'end_date' => '2036-01-01 00:00:00',
            'usage_exceptions' => 'usage',
        ]);

        $allocated = app(TemporalVehicleRevenueAllocator::class)->allocate($entry);

        $this->assertSame('assigned', $allocated->allocation_status);
        $this->assertSame($operational->id, $allocated->vehicle_item_id);
    }

    public function test_it_leaves_missing_dates_and_operational_overlaps_pending(): void
    {
        [$entry, $first] = $this->scenario();
        $entry->update(['occurred_at' => null]);
        $missingDate = app(TemporalVehicleRevenueAllocator::class)->allocate($entry);
        $this->assertSame('pending', $missingDate->allocation_status);
        $this->assertSame('Movimento sem data/hora.', $missingDate->allocation_reason);

        $second = VehicleItem::create(['year' => '2035', 'license_plate' => 'TEST-02', 'is_service_vehicle' => false]);
        foreach ([$first, $second] as $vehicle) {
            VehicleUsage::create([
                'driver_id' => $entry->driver_id,
                'vehicle_item_id' => $vehicle->id,
                'start_date' => '2035-01-06 08:00:00',
                'end_date' => '2035-01-06 12:00:00',
                'usage_exceptions' => 'usage',
            ]);
        }
        $entry->update(['occurred_at' => '2035-01-06 10:00:00']);
        $overlap = app(TemporalVehicleRevenueAllocator::class)->allocate($entry);
        $this->assertSame('pending', $overlap->allocation_status);
        $this->assertSame('Utilizacoes operacionais sobrepostas.', $overlap->allocation_reason);
    }

    private function scenario(): array
    {
        $company = Company::create([
            'name' => 'Temporal Test', 'vat' => uniqid(), 'address' => 'Test',
            'zip' => '0000-000', 'location' => 'Test', 'email' => uniqid().'@example.test',
        ]);
        $driver = Driver::create(['code' => uniqid('driver-'), 'name' => 'Temporal Driver']);
        $week = TvdeWeek::create(['number' => 2, 'start_date' => '2035-01-01', 'end_date' => '2035-01-07']);
        $operator = TvdeOperator::create(['name' => uniqid('operator-')]);
        $model = VehicleModel::create(['name' => 'Operational']);
        $operational = VehicleItem::create([
            'vehicle_model_id' => $model->id, 'year' => '2035',
            'license_plate' => 'TEST-01', 'is_service_vehicle' => false,
        ]);
        $service = VehicleItem::create([
            'vehicle_model_id' => $model->id, 'year' => '2035',
            'license_plate' => 'TEST-SVC', 'is_service_vehicle' => true,
        ]);
        $entry = TvdeActivityEntry::create([
            'tvde_week_id' => $week->id, 'tvde_operator_id' => $operator->id,
            'company_id' => $company->id, 'driver_id' => $driver->id,
            'driver_code' => $driver->code, 'occurred_at' => '2035-01-06 10:00:00',
            'gross' => 100, 'net' => 75, 'tips' => 0,
            'source_hash' => hash('sha256', uniqid('', true)),
        ]);

        return [$entry, $operational, $service];
    }
}
