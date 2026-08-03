<?php

namespace Tests\Unit;

use App\Services\MovviChargeImportService;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MovviChargeImportServiceTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_it_reads_only_the_por_motorista_sheet(): void
    {
        $path = $this->workbook('2032-W31', [
            [139, 'Motorista A', 'AA-00-AA', 6, 194.70, 58.41, 0.5],
            [9, 'Motorista B', 'BB-00-BB', 5, 190.00, 57.00, 0.5],
        ]);

        $result = app(MovviChargeImportService::class)->parse($path, 'movvi_charge_2032-W31.xlsx');

        $this->assertSame(2032, $result['year']);
        $this->assertSame(31, $result['week']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame(115.41, collect($result['rows'])->sum('value'));
    }

    public function test_it_rejects_a_filename_and_sheet_week_mismatch(): void
    {
        $path = $this->workbook('2032-W31', [
            [1, 'Motorista', 'AA-00-AA', 1, 10, 3, 1],
        ]);

        $this->expectException(ValidationException::class);

        app(MovviChargeImportService::class)->parse($path, 'movvi_charge_2032-W30.xlsx');
    }

    private function workbook(string $weekCode, array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('Debitos')->setCellValue('A1', 'Ignorar esta folha');
        $sheet = $spreadsheet->createSheet();
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

        return $path;
    }
}
