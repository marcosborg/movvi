<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            if (!Schema::hasColumn('cards', 'vehicle_item_id')) {
                $table->unsignedBigInteger('vehicle_item_id')->nullable()->after('company_id');
                $table->index('vehicle_item_id', 'cards_vehicle_item_id_index');
                $table->foreign('vehicle_item_id', 'cards_vehicle_item_id_foreign')
                    ->references('id')
                    ->on('vehicle_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            if (Schema::hasColumn('cards', 'vehicle_item_id')) {
                $table->dropForeign('cards_vehicle_item_id_foreign');
                $table->dropIndex('cards_vehicle_item_id_index');
                $table->dropColumn('vehicle_item_id');
            }
        });
    }
};
