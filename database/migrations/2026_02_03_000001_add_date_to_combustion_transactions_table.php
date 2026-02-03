<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combustion_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('combustion_transactions', 'date')) {
                $table->dateTime('date')->nullable()->after('total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('combustion_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('combustion_transactions', 'date')) {
                $table->dropColumn('date');
            }
        });
    }
};

