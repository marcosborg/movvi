<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conta_azul_connections', function (Blueprint $table) {
            if (!Schema::hasColumn('conta_azul_connections', 'receivable_contact_id')) {
                $table->string('receivable_contact_id')->nullable()->after('last_error');
            }

            if (!Schema::hasColumn('conta_azul_connections', 'receivable_financial_account_id')) {
                $table->string('receivable_financial_account_id')->nullable()->after('receivable_contact_id');
            }

            if (!Schema::hasColumn('conta_azul_connections', 'receivable_payment_method')) {
                $table->string('receivable_payment_method')->default('TRANSFERENCIA_BANCARIA')->after('receivable_financial_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conta_azul_connections', function (Blueprint $table) {
            foreach (['receivable_payment_method', 'receivable_financial_account_id', 'receivable_contact_id'] as $column) {
                if (Schema::hasColumn('conta_azul_connections', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
