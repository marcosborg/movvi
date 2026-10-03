<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OperationalSupportFeaturesTest extends TestCase
{
    public function test_driver_entry_date_is_explicit_and_visible_in_the_list(): void
    {
        $translations = file_get_contents(__DIR__.'/../../resources/lang/pt/cruds.php');
        $index = file_get_contents(__DIR__.'/../../resources/views/admin/drivers/index.blade.php');
        $create = file_get_contents(__DIR__.'/../../resources/views/admin/drivers/create.blade.php');

        $this->assertStringContainsString("'start_date'            => 'Data de entrada do condutor'", $translations);
        $this->assertStringContainsString("{ data: 'start_date', name: 'start_date' }", $index);
        $this->assertStringContainsString('id="start_date" value="{{ old(\'start_date\') }}" required', $create);
    }

    public function test_company_expense_form_supports_accounts_payable_fields(): void
    {
        $create = file_get_contents(__DIR__.'/../../resources/views/admin/companyExpenses/create.blade.php');

        foreach (['recurrence', 'category', 'supplier', 'reference', 'due_date', 'payment_status', 'paid_at'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $create, $field);
        }
    }

    public function test_vehicle_usage_page_displays_audit_history(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/admin/vehicleUsages/show.blade.php');

        $this->assertStringContainsString('Histórico de alterações', $view);
        $this->assertStringContainsString('$vehicleUsage->audits', $view);
    }
}
