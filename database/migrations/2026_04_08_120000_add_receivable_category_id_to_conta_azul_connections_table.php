<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conta_azul_connections', function (Blueprint $table) {
            if (! Schema::hasColumn('conta_azul_connections', 'receivable_category_id')) {
                $table->string('receivable_category_id')->nullable()->after('receivable_financial_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('conta_azul_connections', function (Blueprint $table) {
            if (Schema::hasColumn('conta_azul_connections', 'receivable_category_id')) {
                $table->dropColumn('receivable_category_id');
            }
        });
    }
};
