<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers_balances', function (Blueprint $table) {
            $table->string('manual_status')->nullable()->after('new_balance');
        });
    }

    public function down(): void
    {
        Schema::table('drivers_balances', function (Blueprint $table) {
            $table->dropColumn('manual_status');
        });
    }
};
