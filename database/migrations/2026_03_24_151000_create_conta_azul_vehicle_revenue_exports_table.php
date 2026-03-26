<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conta_azul_vehicle_revenue_exports')) {
            return;
        }

        Schema::create('conta_azul_vehicle_revenue_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('tvde_week_id');
            $table->unsignedBigInteger('vehicle_item_id');
            $table->string('license_plate');
            $table->decimal('amount', 12, 2);
            $table->string('description');
            $table->string('status')->default('exported');
            $table->string('conta_azul_event_id')->nullable();
            $table->string('conta_azul_installment_id')->nullable();
            $table->string('conta_azul_acquittance_id')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('event_payload')->nullable();
            $table->json('installment_payload')->nullable();
            $table->json('acquittance_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->unsignedBigInteger('exported_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'tvde_week_id', 'vehicle_item_id'], 'caz_vehicle_week_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conta_azul_vehicle_revenue_exports');
    }
};
