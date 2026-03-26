<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combustion_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('combustion_transactions', 'vehicle_item_id')) {
                $table->unsignedBigInteger('vehicle_item_id')->nullable()->after('tvde_week_id');
                $table->index('vehicle_item_id', 'combustion_transactions_vehicle_item_id_index');
                $table->foreign('vehicle_item_id', 'combustion_transactions_vehicle_item_id_foreign')
                    ->references('id')
                    ->on('vehicle_items')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('combustion_transactions', 'driver_id')) {
                $table->unsignedBigInteger('driver_id')->nullable()->after('vehicle_item_id');
                $table->index('driver_id', 'combustion_transactions_driver_id_index');
                $table->foreign('driver_id', 'combustion_transactions_driver_id_foreign')
                    ->references('id')
                    ->on('drivers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('combustion_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('combustion_transactions', 'driver_id')) {
                $table->dropForeign('combustion_transactions_driver_id_foreign');
                $table->dropIndex('combustion_transactions_driver_id_index');
                $table->dropColumn('driver_id');
            }

            if (Schema::hasColumn('combustion_transactions', 'vehicle_item_id')) {
                $table->dropForeign('combustion_transactions_vehicle_item_id_foreign');
                $table->dropIndex('combustion_transactions_vehicle_item_id_index');
                $table->dropColumn('vehicle_item_id');
            }
        });
    }
};
