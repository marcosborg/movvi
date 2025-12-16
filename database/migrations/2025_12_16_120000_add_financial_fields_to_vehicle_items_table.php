<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinancialFieldsToVehicleItemsTable extends Migration
{
    public function up()
    {
        Schema::table('vehicle_items', function (Blueprint $table) {
            $table->date('acquisition_date')->nullable()->after('suspended');
            $table->decimal('acquisition_value', 15, 2)->nullable()->after('acquisition_date');
            $table->date('sale_date')->nullable()->after('acquisition_value');
            $table->decimal('sale_value', 15, 2)->nullable()->after('sale_date');
        });
    }

    public function down()
    {
        Schema::table('vehicle_items', function (Blueprint $table) {
            $table->dropColumn([
                'acquisition_date',
                'acquisition_value',
                'sale_date',
                'sale_value',
            ]);
        });
    }
}
