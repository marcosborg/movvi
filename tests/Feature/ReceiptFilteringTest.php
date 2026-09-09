<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceiptFilteringTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->withoutMiddleware();
        Gate::before(fn (?\App\Models\User $user) => true);
        Schema::create('drivers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->integer('company_id')->nullable();
            $t->integer('contract_vat_id'); $t->softDeletes();
        });
        Schema::create('companies', function (Blueprint $t) { $t->id(); $t->softDeletes(); });
        Schema::create('contract_vats', function (Blueprint $t) {
            $t->id(); $t->float('iva'); $t->float('rf'); $t->softDeletes();
        });
        Schema::create('tvde_weeks', function (Blueprint $t) {
            $t->id(); $t->date('start_date'); $t->softDeletes();
        });
        Schema::create('receipts', function (Blueprint $t) {
            $t->id(); $t->integer('driver_id'); $t->integer('tvde_week_id');
            $t->float('value'); $t->float('balance'); $t->boolean('paid');
            $t->boolean('verified')->default(true); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('media', function (Blueprint $t) {
            $t->id(); $t->string('model_type'); $t->integer('model_id');
            $t->string('collection_name'); $t->integer('order_column');
        });
        DB::table('contract_vats')->insert(['id' => 1, 'iva' => 6, 'rf' => 0]);
        DB::table('drivers')->insert([
            ['id' => 1, 'name' => 'Ana Silva', 'contract_vat_id' => 1],
            ['id' => 2, 'name' => 'Bruno Costa', 'contract_vat_id' => 1],
        ]);
        DB::table('tvde_weeks')->insert([
            ['id' => 1, 'start_date' => '2026-08-24'], ['id' => 2, 'start_date' => '2026-08-31'],
        ]);
        foreach ([[1, 1, 1, true], [2, 1, 2, true], [3, 2, 2, true], [4, 1, 2, false]] as [$id, $driver, $week, $paid]) {
            DB::table('receipts')->insert(['id' => $id, 'driver_id' => $driver, 'tvde_week_id' => $week,
                'value' => 100, 'balance' => 100, 'paid' => $paid]);
        }
    }

    private function receipts(string $path, string $driver = '', string $week = '')
    {
        $this->app->forgetInstance('datatables.request');
        $columns = [];
        foreach ([['id', 'receipts.id', ''], ['driver_name', 'driver.name', $driver],
            ['tvde_week_start_date', 'tvde_weeks.start_date', $week]] as [$data, $name, $value]) {
            $columns[] = compact('data', 'name') + ['searchable' => 'true', 'orderable' => 'true',
                'search' => ['value' => $value, 'regex' => 'false']];
        }
        return $this->getJson($path . '?' . http_build_query([
            'draw' => 1, 'start' => 0, 'length' => 100, 'columns' => $columns,
            'search' => ['value' => '', 'regex' => 'false'],
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonMissingPath('error');
    }

    public function test_paid_receipts_filter_by_driver_and_week_and_clear(): void
    {
        $this->receipts('/admin/receipts/paid', 'Ana Silva')->assertJsonPath('recordsFiltered', 2)
            ->assertJsonPath('data.0.driver_name', 'Ana Silva')->assertJsonPath('data.1.driver_name', 'Ana Silva');
        $this->receipts('/admin/receipts/paid', 'Ana Silva', '2026-08-31')->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', 2);
        $this->receipts('/admin/receipts/paid', 'No matching driver')->assertJsonPath('recordsFiltered', 0);
        $this->receipts('/admin/receipts/paid')->assertJsonPath('recordsFiltered', 3);
        $this->receipts('/admin/receipts', 'Ana Silva')->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.id', 4);
    }
}
