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
}
