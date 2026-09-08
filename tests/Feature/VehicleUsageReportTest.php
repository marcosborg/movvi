<?php
namespace Tests\Feature;

use App\Models\VehicleItem;
use App\Models\VehicleUsage;
use App\Services\VehicleUsageReportService;
use Carbon\Carbon;
use Tests\TestCase;

class VehicleUsageReportTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); Carbon::setTestNow('2026-09-08 10:00:00'); }
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
    private function vehicle(array $attributes = []): VehicleItem {
        $v = new VehicleItem; $v->setRawAttributes($attributes + ['id'=>1,'license_plate'=>'TEST-01'], true);
        $v->setRelation('vehicle_model', null); return $v;
    }
    private function usage(int $id, string $from, ?string $to, string $category = 'usage'): VehicleUsage {
        $u = new VehicleUsage; $u->setRawAttributes(['id'=>$id,'vehicle_item_id'=>1,'driver_id'=>1,'start_date'=>$from,'end_date'=>$to,'usage_exceptions'=>$category], true);
        $u->setRelation('driver', null); return $u;
    }
    private function report(array $usages, string $from='2026-01-01', string $to='2026-09-08', array $vehicle=[]): array {
        return (new VehicleUsageReportService)->build(collect([$this->vehicle($vehicle)]), collect($usages), $from, $to);
    }
    public function test_current_year_stops_today_and_open_usage_is_included(): void {
        $r=$this->report([$this->usage(1,'2025-01-01',null)],'2026-01-01','2027-12-31');
        $this->assertSame('2026-09-08',$r['to']);
        $this->assertEquals(251,$r['rows'][0]['stats']['total']);
        $this->assertEquals(251,$r['rows'][0]['stats']['usage']);
        $this->assertSame([2026],array_keys($r['rows'][0]['years']));
        $this->assertEquals(100,$r['rows'][0]['stats']['percent']);
    }
    public function test_overlapping_records_do_not_double_count_and_exception_is_not_occupancy(): void {
        $r=$this->report([$this->usage(1,'2026-09-01','2027-01-01'),$this->usage(2,'2026-09-02','2026-09-04','maintenance'),$this->usage(3,'2026-09-05',null)] ,'2026-09-01','2026-09-08');
        $s=$r['rows'][0]['stats'];
        $this->assertEquals(8,$s['total']); $this->assertEquals(6,$s['usage']);
        $this->assertEquals(2,$s['maintenance']); $this->assertEquals(75,$s['percent']);
        $this->assertEquals($s,$r['rows'][0]['months']['2026-09']);
        $this->assertEquals($s,$r['rows'][0]['years'][2026]);
    }
    public function test_gaps_and_future_assignments_do_not_inflate_occupancy(): void {
        $r=$this->report([$this->usage(1,'2026-09-01','2026-09-02'),$this->usage(2,'2027-01-01',null)],'2026-09-01','2026-09-08');
        $s=$r['rows'][0]['stats']; $this->assertEquals(1,$s['usage']);
        $this->assertEquals(7,$s['unassigned']); $this->assertEquals(12.5,$s['percent']);
    }
    public function test_leap_year_and_partial_day_are_consistent(): void {
        $r=$this->report([$this->usage(1,'2024-01-01 12:00:00','2025-01-01')],'2024-01-01','2024-12-31');
        $this->assertEquals(365.5,$r['fleet']['total']); $this->assertEquals(365.5,$r['fleet']['usage']);
    }
    public function test_first_usage_and_sale_clip_denominator_regardless_of_acquisition(): void {
        $r=$this->report([$this->usage(1,'2025-01-01',null)],'2026-01-01','2026-09-08',['acquisition_date'=>'2026-09-02','sale_date'=>'2026-09-04']);
        $this->assertEquals(247,$r['fleet']['total']); $this->assertEquals(247,$r['fleet']['usage']);
    }
    public function test_vehicle_never_used_is_excluded(): void {
        $r=$this->report([]); $this->assertCount(0,$r['rows']);
    }
    public function test_first_usage_before_filter_preserves_idle_days_and_ignores_record_creation(): void {
        $r=$this->report([], '2026-09-01','2026-09-06', ['first_usage_at'=>'2026-08-15 00:00:00','created_at'=>'2026-09-04']);
        $this->assertEquals(6,$r['fleet']['total']);
        $this->assertEquals(6,$r['fleet']['idle']);
    }
    public function test_first_usage_clips_year_and_periods_reconcile(): void {
        $r=$this->report([$this->usage(1,'2026-09-01','2026-09-03')], '2026-01-01','2026-09-06');
        $s=$r['rows'][0];
        $this->assertEquals(6,$s['stats']['total']);
        $this->assertEquals(2,$s['stats']['usage']);
        $this->assertEquals(4,$s['stats']['idle']);
        $this->assertEquals($s['stats'],$s['weeks']['2026-W36']);
        $this->assertEquals($s['stats'],$s['months']['2026-09']);
    }
    public function test_future_first_usage_has_no_elapsed_days(): void {
        $r=$this->report([$this->usage(1,'2026-09-10',null)]);
        $this->assertEmpty($r['rows']);
    }
    private function revenueReport(string $from, string $to, array $vehicle = [], array $weeks = []): array {
        $vehicle += ['first_usage_at'=>'2026-09-01 00:00:00'];
        $weeks = $weeks ?: [['from'=>'2026-08-31','to'=>'2026-09-06','vehicles'=>[1=>['revenue'=>140.0,'missing_accounts'=>0]]]];
        return (new VehicleUsageReportService)->build(collect([$this->vehicle($vehicle)]),
            collect([$this->usage(1,'2026-09-01','2026-09-03')]), $from, $to, $weeks);
    }
    public function test_approved_daily_average_includes_idle_days(): void {
        $r=$this->revenueReport('2026-01-01','2026-09-06'); $s=$r['rows'][0]['stats'];
        $this->assertEqualsWithDelta(140,$s['revenue'],0.00001);
        $this->assertEquals(6,$s['total']); $this->assertEquals(4,$s['idle']);
        $this->assertEqualsWithDelta(140/6,$s['daily_average'],0.00001);
        $this->assertEquals($s,$r['rows'][0]['weeks']['2026-W36']);
    }
    public function test_partial_filters_do_not_reallocate_the_whole_week(): void {
        $a=$this->revenueReport('2026-09-01','2026-09-02')['fleet'];
        $b=$this->revenueReport('2026-09-03','2026-09-06')['fleet'];
        $this->assertEqualsWithDelta(140,$a['revenue']+$b['revenue'],0.00001);
        $this->assertEqualsWithDelta(140/3,$a['revenue'],0.00001);
    }
    public function test_week_crossing_year_and_month_preserves_revenue_and_iso_week(): void {
        $r=$this->revenueReport('2025-12-29','2026-01-04',['first_usage_at'=>'2025-12-29'],[
            ['from'=>'2025-12-29','to'=>'2026-01-04','vehicles'=>[1=>['revenue'=>700.0,'missing_accounts'=>1]]]
        ]); $row=$r['rows'][0];
        $this->assertEqualsWithDelta(300,$row['years'][2025]['revenue'],0.00001);
        $this->assertEqualsWithDelta(400,$row['years'][2026]['revenue'],0.00001);
        $this->assertEqualsWithDelta(700,$row['weeks']['2026-W01']['revenue'],0.00001);
        $this->assertTrue($r['fleet']['incomplete']);
    }
    public function test_current_week_and_sale_cap_allocation_without_future_days(): void {
        $weeks=[['from'=>'2026-09-07','to'=>'2026-09-13','vehicles'=>[1=>['revenue'=>100.0,'missing_accounts'=>0]]]];
        $r=$this->revenueReport('2026-09-07','2026-09-08',['first_usage_at'=>'2026-09-07'],$weeks);
        $this->assertEquals(2,$r['fleet']['total']); $this->assertEquals(100,$r['fleet']['revenue']);
        $r=$this->revenueReport('2026-09-01','2026-09-06',['sale_date'=>'2026-09-03']);
        $this->assertEquals(3,$r['fleet']['total']); $this->assertEqualsWithDelta(140,$r['fleet']['revenue'],0.00001);
    }
    public function test_partial_first_day_uses_wall_time_and_preserves_negative_adjustments(): void {
        $r=$this->revenueReport('2026-09-01','2026-09-06',['first_usage_at'=>'2026-09-01 12:00:00'],[
            ['from'=>'2026-08-31','to'=>'2026-09-06','vehicles'=>[1=>['revenue'=>-55.0,'missing_accounts'=>0]]]
        ]);
        $this->assertEquals(5.5,$r['fleet']['total']);
        $this->assertEqualsWithDelta(-10,$r['fleet']['daily_average'],0.00001);
    }
}
