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
        Schema::create('vehicle_items',function(Blueprint $t){$t->id();$t->string('license_plate');$t->integer('company_id');$t->integer('vehicle_model_id')->nullable();$t->integer('vehicle_brand_id')->nullable();$t->boolean('suspended')->default(false);$t->date('sale_date')->nullable();$t->softDeletes();});
        Schema::create('vehicle_usages',function(Blueprint $t){$t->id();$t->integer('vehicle_item_id');$t->integer('driver_id')->nullable();$t->string('usage_exceptions')->nullable();$t->dateTime('start_date');$t->dateTime('end_date')->nullable();$t->softDeletes();});
        Schema::create('drivers',function(Blueprint $t){$t->id();$t->string('name');$t->softDeletes();});
        DB::table('companies')->insert(['id'=>1,'name'=>'Test Company']);
        DB::table('vehicle_models')->insert(['id'=>1,'name'=>'Model A']);
        DB::table('vehicle_items')->insert([['id'=>1,'company_id'=>1,'license_plate'=>'TEST-01','vehicle_model_id'=>1],['id'=>2,'company_id'=>2,'license_plate'=>'FOREIGN-02','vehicle_model_id'=>null]]);
        DB::table('vehicle_usages')->insert(['vehicle_item_id'=>1,'start_date'=>'2026-09-01','end_date'=>null,'usage_exceptions'=>'usage']);
        $this->mock(\App\Services\VehicleUsageRevenueService::class, fn ($mock) => $mock->shouldReceive('weeks')->andReturn([]));
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
    public function test_active_filter_excludes_suspended_sold_and_never_used(): void {
        foreach ([3=>'SUSPENDED',4=>'SOLD',5=>'NEVER'] as $id=>$plate) {
            DB::table('vehicle_items')->insert(['id'=>$id,'company_id'=>1,'license_plate'=>$plate,'suspended'=>$id===3,'sale_date'=>$id===4?'2026-09-02':null]);
            if ($id!==5) DB::table('vehicle_usages')->insert(['vehicle_item_id'=>$id,'start_date'=>'2026-09-01','usage_exceptions'=>'usage']);
        }
        session(['company_id'=>1]);
        $controller=new \App\Http\Controllers\Admin\VehicleUsageController;
        $view=$controller->usage(\Illuminate\Http\Request::create('/admin/vehicle-usage'));
        $this->assertSame(['TEST-01'],array_column($view->getData()['report']['rows'],'plate'));
        $view=$controller->usage(\Illuminate\Http\Request::create('/admin/vehicle-usage','GET',['active_only'=>'0']));
        $this->assertCount(3,$view->getData()['report']['rows']);
    }
    public function test_month_shortcut_and_active_filter_use_the_selected_period(): void {
        DB::table('vehicle_items')->insert([
            ['id'=>6,'company_id'=>1,'license_plate'=>'ACTIVE-AUG','vehicle_model_id'=>1],
            ['id'=>7,'company_id'=>1,'license_plate'=>'ACTIVE-OCT','vehicle_model_id'=>1],
        ]);
        DB::table('vehicle_usages')->insert([
            ['vehicle_item_id'=>6,'start_date'=>'2026-07-15','end_date'=>null,'usage_exceptions'=>'usage'],
            ['vehicle_item_id'=>7,'start_date'=>'2026-10-01','end_date'=>null,'usage_exceptions'=>'usage'],
        ]);
        session(['company_id'=>1]);
        $view=(new \App\Http\Controllers\Admin\VehicleUsageController)->usage(
            \Illuminate\Http\Request::create('/admin/vehicle-usage','GET',['month'=>'2026-08'])
        );
        $data=$view->getData();
        $this->assertSame('2026-08-01',$data['from']);
        $this->assertSame('2026-08-31',$data['to']);
        $this->assertSame(['ACTIVE-AUG'],array_column($data['report']['rows'],'plate'));
    }
    public function test_financial_values_require_profitability_permission(): void {
        session(['company_id'=>1]);
        Gate::shouldReceive('denies')->with('vehicle_usage_access')->andReturn(false);
        Gate::shouldReceive('allows')->with('vehicle_profitability_access')->andReturn(false);
        $this->mock(\App\Services\VehicleUsageRevenueService::class, fn($mock)=>$mock->shouldNotReceive('weeks'));
        $view=(new \App\Http\Controllers\Admin\VehicleUsageController)->usage(\Illuminate\Http\Request::create('/admin/vehicle-usage'));
        $this->assertFalse($view->getData()['canViewRevenue']);
        $html=view('admin.vehicleUsages.usage-report', $view->getData()+['pdf'=>true])->render();
        $this->assertStringNotContainsString('Faturação total',$html);
        $this->assertStringContainsString('Dias sem uso',$html);
    }
    public function test_historical_first_usage_is_available_when_selected_period_has_no_usage(): void {
        DB::table('vehicle_usages')->update(['start_date'=>'2026-08-01','end_date'=>'2026-08-02']);
        session(['company_id'=>1]);
        $view=(new \App\Http\Controllers\Admin\VehicleUsageController)->usage(\Illuminate\Http\Request::create('/admin/vehicle-usage','GET',['from'=>'2026-09-01','to'=>'2026-09-06']));
        $stats=$view->getData()['report']['rows'][0]['stats'];
        $this->assertEquals(6,$stats['idle']);
        $this->assertEquals(0,$stats['usage']);
    }
}
