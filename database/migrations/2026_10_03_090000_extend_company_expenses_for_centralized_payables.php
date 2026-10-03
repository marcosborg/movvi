<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_expenses', function (Blueprint $table) {
            $table->string('recurrence', 20)->default('weekly')->after('weekly_value');
            $table->string('category')->nullable()->after('name');
            $table->string('supplier')->nullable()->after('category');
            $table->string('reference')->nullable()->after('supplier');
            $table->date('due_date')->nullable()->after('end_date');
            $table->date('paid_at')->nullable()->after('due_date');
            $table->string('payment_status', 20)->default('pending')->after('paid_at');
            $table->text('notes')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('company_expenses', function (Blueprint $table) {
            $table->dropColumn([
                'recurrence', 'category', 'supplier', 'reference', 'due_date',
                'paid_at', 'payment_status', 'notes',
            ]);
        });
    }
};
