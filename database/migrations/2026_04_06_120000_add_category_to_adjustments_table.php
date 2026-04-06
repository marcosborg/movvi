<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('adjustments', 'category')) {
                $table->string('category')->nullable()->after('percent');
            }
        });

        DB::table('adjustments')
            ->whereNull('category')
            ->update(['category' => 'general']);
    }

    public function down(): void
    {
        Schema::table('adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('adjustments', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
