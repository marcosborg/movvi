<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('weekly_vehicle_mileages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tvde_week_id');
            $table->string('license_plate');
            $table->string('description')->nullable();
            $table->decimal('odometer_start', 12, 2)->nullable();
            $table->decimal('odometer_end', 12, 2)->nullable();
            $table->decimal('distance_km', 12, 2);
            $table->dateTime('source_period_start')->nullable();
            $table->dateTime('source_period_end')->nullable();
            $table->timestamps();

            $table->foreign('tvde_week_id')->references('id')->on('tvde_weeks')->cascadeOnDelete();
            $table->unique(['tvde_week_id', 'license_plate'], 'weekly_vehicle_mileages_week_plate_unique');
            $table->index('license_plate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_vehicle_mileages');
    }
};
