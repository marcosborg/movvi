<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('stand_cars', 'year')) {
            Schema::table('stand_cars', function (Blueprint $table) {
                $table->dropColumn('year');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasColumn('stand_cars', 'year')) {
            Schema::table('stand_cars', function (Blueprint $table) {
                $table->integer('year')->nullable()->after('battery_capacity');
            });
        }
    }
};
