<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tesla_chargings', function (Blueprint $table) {
            if (!Schema::hasColumn('tesla_chargings', 'card_type')) {
                $table->string('card_type')->default('Tesla')->after('datetime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tesla_chargings', function (Blueprint $table) {
            if (Schema::hasColumn('tesla_chargings', 'card_type')) {
                $table->dropColumn('card_type');
            }
        });
    }
};
