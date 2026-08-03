<?php

namespace Tests\Feature;

use App\Models\CurrentAccount;
use App\Models\Driver;
use App\Models\MovviChargeImport;
use App\Models\TvdeWeek;
use App\Services\MovviChargeImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MovviChargeImportTest extends TestCase
{
    use DatabaseTransactions;

    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_it_imports_valid_drivers_ignores_unknown_ids_and_replaces_the_week(): void
    {
        $week = $this->createWeek(2032, 31);
        $driver = Driver::create(['code' => 'MOVVI-CHARGE-TEST', 'name' => 'Motorista Teste']);
        $service = app(MovviChargeImportService::class);

        $firstFile = $this->upload('2032-W31', [
            [$driver->id, $driver->name, 'AA-00-AA', 2, 20.50, 6.15, 0.5],
            [999999, 'Inexistente', 'XX-00-XX', 1, 10, 3, 0.5],
        ]);
        $first = $service->import($firstFile, null);

        $this->assertSame([$driver->id], MovviChargeImport::firstOrFail()->entries->pluck('driver_id')->all());
        $this->assertSame([999999], $first['unknown_driver_ids']);
        $this->assertSame(6.15, $first['total_value']);
        $this->assertFalse($first['was_replacement']);

        $secondFile = $this->upload('2032-W31', [
            [$driver->id, $driver->name, 'AA-00-AA', 4, 40, 12, 1],
        ]);
        $second = $service->import($secondFile, null);

        $this->assertTrue($second['was_replacement']);
        $this->assertSame(1, MovviChargeImport::where('tvde_week_id', $week->id)->count());
        $this->assertSame(12.0, (float) MovviChargeImport::firstOrFail()->entries->first()->value);
    }

    public function test_it_blocks_an_import_for_a_closed_week(): void
    {
        $week = $this->createWeek(2033, 31);
        $driver = Driver::create(['code' => 'MOVVI-CHARGE-CLOSED', 'name' => 'Motorista Fechado']);
        CurrentAccount::create([
            'tvde_week_id' => $week->id,
            'driver_id' => $driver->id,
            'data' => '{}',
        ]);

        $this->expectException(ValidationException::class);

        app(MovviChargeImportService::class)->import($this->upload('2033-W31', [
            [$driver->id, $driver->name, 'AA-00-AA', 1, 10, 3, 1],
        ]), null);
    }

    private function createWeek(int $year, int $week): TvdeWeek
    {
        $start = now()->setISODate($year, $week)->startOfWeek();
        $id = DB::table('tvde_weeks')->insertGetId([
            'number' => $week,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->endOfWeek()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TvdeWeek::findOrFail($id);
    }

    private function upload(string $weekCode, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Por Motorista');
        $sheet->fromArray([
            ['Movvi Charge — Por Motorista '.$weekCode],
            ['ID', 'Motorista', 'Matricula', 'Sessoes', 'kWh', 'Valor (EUR)', '%'],
            ...$rows,
            ['TOTAL'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'movvi-charge-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, 'movvi_charge_'.$weekCode.'.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
