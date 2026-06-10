<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_tracks', function (Blueprint $table) {
            if (!Schema::hasColumn('car_tracks', 'vehicle_item_id')) {
                $table->unsignedBigInteger('vehicle_item_id')->nullable()->after('tvde_week_id');
                $table->index('vehicle_item_id', 'car_tracks_vehicle_item_id_index');
                $table->foreign('vehicle_item_id', 'car_tracks_vehicle_item_id_foreign')
                    ->references('id')
                    ->on('vehicle_items')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('car_tracks', 'driver_id')) {
                $table->unsignedBigInteger('driver_id')->nullable()->after('vehicle_item_id');
                $table->index('driver_id', 'car_tracks_driver_id_index');
                $table->foreign('driver_id', 'car_tracks_driver_id_foreign')
                    ->references('id')
                    ->on('drivers')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('car_tracks', 'vehicle_usage_id')) {
                $table->unsignedBigInteger('vehicle_usage_id')->nullable()->after('driver_id');
                $table->index('vehicle_usage_id', 'car_tracks_vehicle_usage_id_index');
                $table->foreign('vehicle_usage_id', 'car_tracks_vehicle_usage_id_foreign')
                    ->references('id')
                    ->on('vehicle_usages')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('car_tracks', 'assignment_status')) {
                $table->string('assignment_status')->nullable()->after('vehicle_usage_id')->index('car_tracks_assignment_status_index');
            }

            if (!Schema::hasColumn('car_tracks', 'assignment_notes')) {
                $table->text('assignment_notes')->nullable()->after('assignment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('car_tracks', function (Blueprint $table) {
            if (Schema::hasColumn('car_tracks', 'assignment_notes')) {
                $table->dropColumn('assignment_notes');
            }

            if (Schema::hasColumn('car_tracks', 'assignment_status')) {
                $table->dropIndex('car_tracks_assignment_status_index');
                $table->dropColumn('assignment_status');
            }

            if (Schema::hasColumn('car_tracks', 'vehicle_usage_id')) {
                $table->dropForeign('car_tracks_vehicle_usage_id_foreign');
                $table->dropIndex('car_tracks_vehicle_usage_id_index');
                $table->dropColumn('vehicle_usage_id');
            }

            if (Schema::hasColumn('car_tracks', 'driver_id')) {
                $table->dropForeign('car_tracks_driver_id_foreign');
                $table->dropIndex('car_tracks_driver_id_index');
                $table->dropColumn('driver_id');
            }

            if (Schema::hasColumn('car_tracks', 'vehicle_item_id')) {
                $table->dropForeign('car_tracks_vehicle_item_id_foreign');
                $table->dropIndex('car_tracks_vehicle_item_id_index');
                $table->dropColumn('vehicle_item_id');
            }
        });
    }
};
