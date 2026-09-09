<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CurrentAccount;
use App\Models\Driver;
use App\Models\TvdeActivityEntry;
use App\Models\TvdeOperator;
use App\Models\TvdeWeek;
use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use App\Services\VehicleProfitabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehicleProfitabilityServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pending_entries_fall_back_to_vehicle_usage_time(): void
    {
        [$company, $driver, $week, $vehicle, $operator] = $this->scenario();

        $this->createEntry($company, $driver, $week, $operator, [
            'allocation_status' => 'pending',
            'vehicle_item_id' => null,
            'net' => 500,
        ]);

        $row = collect(VehicleProfitabilityService::makeWeek($week->id, $company->id)['vehicles'])
            ->firstWhere('id', $vehicle->id);

        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(700.0, $row['rental_total'], 0.001);
        $this->assertEqualsWithDelta(70.0, $row['commission_total'], 0.001);
    }

    public function test_pending_entries_do_not_dilute_allocated_entry_ratios(): void
    {
        [$company, $driver, $week, $vehicle, $operator] = $this->scenario();

        $this->createEntry($company, $driver, $week, $operator, [
            'allocation_status' => 'assigned',
            'vehicle_item_id' => $vehicle->id,
            'net' => 100,
        ]);
        $this->createEntry($company, $driver, $week, $operator, [
            'allocation_status' => 'pending',
            'vehicle_item_id' => null,
            'net' => 900,
        ]);

        $row = collect(VehicleProfitabilityService::makeWeek($week->id, $company->id)['vehicles'])
            ->firstWhere('id', $vehicle->id);

        $this->assertNotNull($row);
        $this->assertEqualsWithDelta(700.0, $row['rental_total'], 0.001);
        $this->assertEqualsWithDelta(70.0, $row['commission_total'], 0.001);
    }

    public function test_service_vehicles_are_not_listed_in_week_profitability(): void
    {
        [$company, $driver, $week, $vehicle] = $this->scenario();
        $vehicle->update(['is_service_vehicle' => true]);

        $row = collect(VehicleProfitabilityService::makeWeek($week->id, $company->id)['vehicles'])
            ->firstWhere('id', $vehicle->id);

        $this->assertNull($row);
    }

    public function test_api_filters_company_and_includes_new_eligible_vehicles(): void
    {
        [$company, $driver, $week, $vehicle, $operator] = $this->scenario();
        $other = $this->scenario();
        $this->createEntry($company, $driver, $week, $operator, [
            'allocation_status' => 'pending', 'vehicle_item_id' => null, 'net' => 500,
        ]);
        $service = $vehicle->replicate();
        $service->license_plate = 'SERVICE-TEST';
        $service->is_service_vehicle = true;
        $service->save();

        $this->withoutMiddleware(\App\Http\Middleware\AuthGates::class);
        $user = new \App\Models\User();
        $user->id = 999;
        \Laravel\Sanctum\Sanctum::actingAs($user);
        \Illuminate\Support\Facades\Gate::define('vehicle_profitability_access', fn () => true);
        $response = $this->getJson('/api/v1/vehicle-profitabilities?tvde_week_id='.$week->id.'&company_id='.$company->id)
            ->assertOk();
        $ids = collect($response->json('data.vehicles'))->pluck('id');
        $this->assertTrue($ids->contains($vehicle->id));
        $this->assertFalse($ids->contains($other[3]->id));
        $this->assertFalse($ids->contains($service->id));
        $global = $this->getJson('/api/v1/vehicle-profitabilities?tvde_week_id='.$week->id)->assertOk();
        $this->assertTrue(collect($global->json('data.vehicles'))->pluck('id')->contains($other[3]->id));
        $this->assertEqualsWithDelta(770, $response->json('data.totals.total_revenue'), 0.001);
        $this->getJson('/api/v1/vehicle-profitabilities?tvde_week_id='.$week->id.'&company_id='.$company->id.'&vehicle_id='.$vehicle->id)
            ->assertOk()->assertJsonPath('mode', 'vehicle');
    }

    private function scenario(): array
    {
        $company = Company::create([
            'name' => 'Profitability Test',
            'vat' => uniqid('vat-'),
            'address' => 'Test',
            'zip' => '0000-000',
            'location' => 'Test',
            'email' => uniqid().'@example.test',
        ]);
        $driver = Driver::create([
            'company_id' => $company->id,
            'code' => uniqid('driver-'),
            'name' => 'Profitability Driver',
        ]);
        $week = TvdeWeek::create([
            'number' => 1,
            'start_date' => '2036-01-07',
            'end_date' => '2036-01-13',
        ]);
        $vehicle = VehicleItem::create([
            'company_id' => $company->id,
            'year' => '2036',
            'license_plate' => uniqid('TEST-'),
            'suspended' => false,
            'is_service_vehicle' => false,
        ]);
        $operator = TvdeOperator::create(['name' => uniqid('operator-')]);

        VehicleUsage::create([
            'driver_id' => $driver->id,
            'vehicle_item_id' => $vehicle->id,
            'start_date' => '2036-01-07 00:00:00',
            'end_date' => '2036-01-13 23:59:59',
            'usage_exceptions' => 'usage',
        ]);
        CurrentAccount::create([
            'tvde_week_id' => $week->id,
            'driver_id' => $driver->id,
            'data' => json_encode(['car_hire' => 700, 'percent_value' => 70]),
        ]);

        return [$company, $driver, $week, $vehicle, $operator];
    }

    private function createEntry($company, $driver, $week, $operator, array $attributes): TvdeActivityEntry
    {
        return TvdeActivityEntry::create(array_merge([
            'tvde_week_id' => $week->id,
            'tvde_operator_id' => $operator->id,
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'driver_code' => $driver->code,
            'occurred_at' => '2036-01-10 10:00:00',
            'gross' => 600,
            'tips' => 0,
            'source_hash' => hash('sha256', uniqid('', true)),
        ], $attributes));
    }
}
