<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('years')) {
            Schema::create('years', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('stand_cars', 'year_id')) {
            Schema::table('stand_cars', function (Blueprint $table) {
                $table->unsignedBigInteger('year_id')->nullable()->after('year');
            });

            if (Schema::hasColumn('stand_cars', 'month_id')) {
                DB::table('stand_cars')->update(['year_id' => DB::raw('month_id')]);
            }
        }

        if (Schema::hasColumn('stand_cars', 'month_id')) {
            Schema::table('stand_cars', function (Blueprint $table) {
                $table->dropColumn('month_id');
            });
        }

        Schema::dropIfExists('months');
    }

    public function down()
    {
        // recreate months table
        Schema::create('months', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (!Schema::hasColumn('stand_cars', 'month_id')) {
            Schema::table('stand_cars', function (Blueprint $table) {
                $table->unsignedBigInteger('month_id')->nullable()->after('year');
            });
        }

        Schema::table('stand_cars', function (Blueprint $table) {
            if (Schema::hasColumn('stand_cars', 'year_id')) {
                DB::table('stand_cars')->update(['month_id' => DB::raw('year_id')]);
                $table->dropColumn('year_id');
            }
        });

        Schema::dropIfExists('years');
    }
};
