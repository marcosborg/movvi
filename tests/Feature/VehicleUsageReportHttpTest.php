<?php
namespace Tests\Feature;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class VehicleUsageReportHttpTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']); DB::purge('sqlite');
        foreach (['vehicle_models','vehicle_brands','companies'] as $table) Schema::create($table,function(Blueprint $t){$t->id();$t->string('name');$t->softDeletes();});
        Schema::create('vehicle_items',function(Blueprint $t){$t->id();$t->string('license_plate');$t->integer('company_id');$t->integer('vehicle_model_id')->nullable();$t->integer('vehicle_brand_id')->nullable();$t->softDeletes();});
        Schema::create('vehicle_usages',function(Blueprint $t){$t->id();$t->integer('vehicle_item_id');$t->integer('driver_id')->nullable();$t->dateTime('start_date');$t->dateTime('end_date')->nullable();$t->softDeletes();});
        DB::table('companies')->insert(['id'=>1,'name'=>'Test Company']);
        DB::table('vehicle_models')->insert(['id'=>1,'name'=>'Model A']);
        DB::table('vehicle_items')->insert([['id'=>1,'company_id'=>1,'license_plate'=>'TEST-01','vehicle_model_id'=>1],['id'=>2,'company_id'=>2,'license_plate'=>'FOREIGN-02','vehicle_model_id'=>null]]);
        $this->withoutMiddleware(); Gate::before(fn($user = null)=>true);
    }
    public function test_pdf_of_selected_vehicle_renders(): void {
        $response=$this->withSession(['company_id'=>1])->get('/admin/vehicle-usage?format=pdf&selection=selected&vehicle_ids[]=1&group=model:1&section=detail');
        $response->assertOk()->assertHeader('content-type','application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
    public function test_vehicle_from_another_company_is_rejected(): void {
        $this->withSession(['company_id'=>1])->get('/admin/vehicle-usage?format=pdf&selection=selected&vehicle_ids[]=2')->assertForbidden();
    }
    public function test_empty_explicit_selection_is_not_exported_as_whole_fleet(): void {
        $this->withSession(['company_id'=>1])->get('/admin/vehicle-usage?format=pdf&selection=selected')->assertStatus(422);
    }
    public function test_future_period_is_rejected(): void {
        $this->withSession(['company_id'=>1])->getJson('/admin/vehicle-usage?format=pdf&to=2099-01-01')->assertStatus(422);
    }
}
