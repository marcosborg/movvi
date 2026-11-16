<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CompanyReportPresentationTest extends TestCase
{
    public function test_company_reports_present_vat_without_receipt_difference_checks(): void
    {
        foreach ([
            'index.blade.php',
            'pdf.blade.php',
            'excel.blade.php',
            'history.blade.php',
            'historyPdf.blade.php',
            'historyExcel.blade.php',
        ] as $view) {
            $contents = file_get_contents(__DIR__.'/../../resources/views/admin/companyReports/'.$view);

            $this->assertStringContainsString('Taxa 6%', $contents, $view);
            $this->assertStringContainsString('NIF', $contents, $view);
            $this->assertStringContainsString('driver_vat', $contents, $view);
            $this->assertStringNotContainsString('Divergente', $contents, $view);
        }
    }

    public function test_import_reconciliation_is_compact_and_details_are_collapsed(): void
    {
        $contents = file_get_contents(__DIR__.'/../../resources/views/admin/companyReports/index.blade.php');

        $this->assertStringContainsString('Motoristas reconhecidos', $contents);
        $this->assertStringContainsString('class="collapse"', $contents);
        $this->assertStringNotContainsString('<h4 style="margin-top:0;">Reconciliação da importação</h4>', $contents);
    }

    public function test_metric_sorting_defaults_to_best_results_first(): void
    {
        $contents = file_get_contents(__DIR__.'/../../resources/views/admin/companyReports/index.blade.php');

        $this->assertStringContainsString('Melhor faturação', $contents);
        $this->assertStringContainsString('Melhor euro/km', $contents);
        $this->assertStringContainsString("sortDirectionFilter.value = sortByFilter.value === 'name' ? 'asc' : 'desc'", $contents);
        $this->assertStringContainsString("addEventListener('change', () => updateSortParams(true))", $contents);
    }

    public function test_driver_and_vehicle_lists_can_load_all_rows_for_export(): void
    {
        foreach (['drivers/index.blade.php', 'vehicleItems/index.blade.php'] as $view) {
            $contents = file_get_contents(__DIR__.'/../../resources/views/admin/'.$view);

            $this->assertStringContainsString("lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'Todos']]", $contents, $view);
        }
    }
}
