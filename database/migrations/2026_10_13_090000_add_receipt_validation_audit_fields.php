<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('verified');
            $table->unsignedBigInteger('verified_by')->nullable()->after('verified_at');
            $table->timestamp('balance_applied_at')->nullable()->after('verified_by');
        });

        DB::table('receipts')
            ->where('verified', true)
            ->update([
                'verified_at' => DB::raw('COALESCE(updated_at, created_at)'),
                'balance_applied_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'verified_by', 'balance_applied_at']);
        });
    }
};
