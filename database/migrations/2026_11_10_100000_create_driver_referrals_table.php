<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('referred_driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('referred_name');
            $table->string('referred_phone', 40);
            $table->string('referred_phone_normalized', 24)->index();
            $table->string('status', 24)->default('submitted')->index();
            $table->decimal('reward_amount', 10, 2)->default(50);
            $table->timestamp('consent_confirmed_at');
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['referrer_driver_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_referrals');
    }
};
