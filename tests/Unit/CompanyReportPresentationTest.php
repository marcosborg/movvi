<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CompanyReportPresentationTest extends TestCase
{
    public function test_company_reports_do_not_present_vat_or_receipt_difference_checks(): void
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

            $this->assertStringNotContainsString('Taxa 6%', $contents, $view);
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
}
