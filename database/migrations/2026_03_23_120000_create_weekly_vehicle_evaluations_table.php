<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_vehicle_evaluations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tvde_week_id');
            $table->unsignedBigInteger('driver_id');
            $table->unsignedBigInteger('vehicle_item_id');
            $table->unsignedBigInteger('submitted_by_user_id')->nullable();
            $table->unsignedInteger('final_mileage')->nullable();
            $table->string('fuel_level', 32)->nullable();
            $table->string('front_tire_status', 32)->nullable();
            $table->string('rear_tire_status', 32)->nullable();
            $table->string('oil_level', 32)->nullable();
            $table->boolean('has_vehicle_issue')->default(false);
            $table->text('issue_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tvde_week_id', 'driver_id', 'vehicle_item_id'], 'weekly_vehicle_eval_unique');
            $table->index(['driver_id', 'submitted_at'], 'weekly_vehicle_eval_driver_submitted_idx');
            $table->index(['vehicle_item_id', 'submitted_at'], 'weekly_vehicle_eval_vehicle_submitted_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_vehicle_evaluations');
    }
};
