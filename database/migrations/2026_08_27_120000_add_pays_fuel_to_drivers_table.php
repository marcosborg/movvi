<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('drivers', 'pays_fuel')) {
            return;
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->boolean('pays_fuel')->default(true)->after('half_tolls');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('drivers', 'pays_fuel')) {
            return;
        }

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('pays_fuel');
        });
    }
};
