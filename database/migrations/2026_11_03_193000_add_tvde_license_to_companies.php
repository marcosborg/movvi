<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'tvde_license')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('tvde_license', 100)->nullable()->after('vat');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'tvde_license')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('tvde_license');
            });
        }
    }
};
