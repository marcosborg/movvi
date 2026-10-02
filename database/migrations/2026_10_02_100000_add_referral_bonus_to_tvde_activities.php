<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tvde_activities') && ! Schema::hasColumn('tvde_activities', 'referral_bonus')) {
            Schema::table('tvde_activities', function (Blueprint $table) {
                $table->decimal('referral_bonus', 12, 2)->default(0)->after('tips');
            });
        }

        if (Schema::hasTable('tvde_activity_entries') && ! Schema::hasColumn('tvde_activity_entries', 'referral_bonus')) {
            Schema::table('tvde_activity_entries', function (Blueprint $table) {
                $table->decimal('referral_bonus', 12, 2)->default(0)->after('tips');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tvde_activity_entries') && Schema::hasColumn('tvde_activity_entries', 'referral_bonus')) {
            Schema::table('tvde_activity_entries', function (Blueprint $table) {
                $table->dropColumn('referral_bonus');
            });
        }

        if (Schema::hasTable('tvde_activities') && Schema::hasColumn('tvde_activities', 'referral_bonus')) {
            Schema::table('tvde_activities', function (Blueprint $table) {
                $table->dropColumn('referral_bonus');
            });
        }
    }
};
