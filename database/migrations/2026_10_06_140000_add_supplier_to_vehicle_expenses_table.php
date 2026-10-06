<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicle_expenses', 'supplier')) {
            Schema::table('vehicle_expenses', function (Blueprint $table) {
                $table->string('supplier')->nullable()->after('expense_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vehicle_expenses', 'supplier')) {
            Schema::table('vehicle_expenses', function (Blueprint $table) {
                $table->dropColumn('supplier');
            });
        }
    }
};
