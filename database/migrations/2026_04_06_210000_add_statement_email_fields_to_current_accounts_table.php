<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('current_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('current_accounts', 'statement_sent_at')) {
                $table->timestamp('statement_sent_at')->nullable()->after('data');
            }

            if (!Schema::hasColumn('current_accounts', 'statement_sent_to')) {
                $table->string('statement_sent_to')->nullable()->after('statement_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('current_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('current_accounts', 'statement_sent_to')) {
                $table->dropColumn('statement_sent_to');
            }

            if (Schema::hasColumn('current_accounts', 'statement_sent_at')) {
                $table->dropColumn('statement_sent_at');
            }
        });
    }
};
