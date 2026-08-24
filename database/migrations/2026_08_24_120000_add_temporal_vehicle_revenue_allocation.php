<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vehicle_items', function (Blueprint $table) {
            $table->boolean('is_service_vehicle')->default(false)->after('suspended')->index();
        });

        Schema::create('tvde_activity_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tvde_week_id')->constrained('tvde_weeks')->cascadeOnDelete();
            $table->foreignId('tvde_operator_id')->constrained('tvde_operators')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('vehicle_item_id')->nullable()->constrained('vehicle_items')->nullOnDelete();
            $table->string('driver_code')->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->decimal('gross', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->decimal('tips', 12, 2)->default(0);
            $table->string('allocation_status', 20)->default('pending');
            $table->string('allocation_reason')->nullable();
            $table->string('source_hash', 64);
            $table->timestamps();
            $table->unique(['tvde_week_id', 'tvde_operator_id', 'company_id', 'source_hash'], 'tvde_entries_source_unique');
            $table->index(['driver_id', 'tvde_week_id', 'allocation_status'], 'tvde_entries_driver_week_status');
        });

        Schema::create('vehicle_revenue_allocation_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tvde_week_id')->constrained('tvde_weeks')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignId('vehicle_item_id')->constrained('vehicle_items')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->unique(['tvde_week_id', 'driver_id'], 'vehicle_revenue_override_week_driver_unique');
        });

        DB::table('vehicle_items')
            ->join('vehicle_models', 'vehicle_models.id', '=', 'vehicle_items.vehicle_model_id')
            ->where(function ($query) {
                $query->whereRaw('LOWER(vehicle_models.name) LIKE ?', ['%viatura de servico%'])
                    ->orWhereRaw('LOWER(vehicle_models.name) LIKE ?', ['%viatura de serviço%']);
            })
            ->update(['vehicle_items.is_service_vehicle' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_revenue_allocation_overrides');
        Schema::dropIfExists('tvde_activity_entries');
        Schema::table('vehicle_items', fn (Blueprint $table) => $table->dropColumn('is_service_vehicle'));
    }
};
