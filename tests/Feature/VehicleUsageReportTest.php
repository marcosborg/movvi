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
        $this->assertEquals(366,$r['fleet']['total']); $this->assertEquals(365.5,$r['fleet']['usage']);
    }
    public function test_acquisition_and_sale_clip_denominator_and_usage(): void {
        $r=$this->report([$this->usage(1,'2025-01-01',null)],'2026-01-01','2026-09-08',['acquisition_date'=>'2026-09-02','sale_date'=>'2026-09-04']);
        $this->assertEquals(3,$r['fleet']['total']); $this->assertEquals(3,$r['fleet']['usage']);
    }
    public function test_vehicle_without_usage_stays_visible_at_zero(): void {
        $r=$this->report([]); $this->assertCount(1,$r['rows']);
        $this->assertEquals(251,$r['fleet']['unassigned']); $this->assertEquals(0,$r['fleet']['percent']);
    }
}
