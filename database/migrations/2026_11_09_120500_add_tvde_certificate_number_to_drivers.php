<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('drivers') || Schema::hasColumn('drivers', 'tvde_certificate_number')) {
            return;
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('tvde_certificate_number')->nullable()->after('driver_license');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('drivers') || ! Schema::hasColumn('drivers', 'tvde_certificate_number')) {
            return;
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('tvde_certificate_number');
        });
    }
};
