<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            DELETE da1 FROM driver_alerts da1
            INNER JOIN driver_alerts da2
                ON da1.driver_id = da2.driver_id
                AND da1.type = da2.type
                AND da1.id > da2.id
        ');

        Schema::table('driver_alerts', function (Blueprint $table) {
            $table->unique(['driver_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('driver_alerts', function (Blueprint $table) {
            $table->dropUnique('driver_alerts_driver_id_type_unique');
        });
    }
};
