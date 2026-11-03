<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        $this->app['db']->purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('admin_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->integer('number');
            $table->date('signed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('vat')->nullable();
        });
    }

    public function test_contract_template_migration_adds_all_versioned_snapshot_fields(): void
    {
        $migration = require database_path('migrations/2026_11_03_184500_add_versioned_contract_templates.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('contract_templates'));
        $this->assertTrue(Schema::hasColumns('contract_templates', [
            'name', 'version', 'content', 'is_active', 'created_by',
        ]));
        $this->assertTrue(Schema::hasColumns('admin_contracts', [
            'contract_template_id', 'template_version', 'source_content', 'rendered_content',
            'data_snapshot', 'generated_at', 'generated_by',
        ]));
    }

    public function test_company_license_migration_adds_the_tvde_license_field(): void
    {
        $migration = require database_path('migrations/2026_11_03_193000_add_tvde_license_to_companies.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('companies', 'tvde_license'));
    }
}
