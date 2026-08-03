<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movvi_charge_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tvde_week_id')->unique()->constrained('tvde_weeks');
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename');
            $table->string('file_hash', 64);
            $table->unsignedInteger('row_count');
            $table->unsignedInteger('total_sessions')->default(0);
            $table->decimal('total_kwh', 12, 2)->default(0);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->timestamp('imported_at');
            $table->timestamps();
        });

        Schema::create('movvi_charge_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movvi_charge_import_id')->constrained('movvi_charge_imports')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers');
            $table->string('driver_name')->nullable();
            $table->string('license_plate', 50)->nullable();
            $table->unsignedInteger('sessions')->default(0);
            $table->decimal('kwh', 12, 2)->default(0);
            $table->decimal('value', 12, 2);
            $table->timestamps();

            $table->unique(['movvi_charge_import_id', 'driver_id'], 'movvi_charge_import_driver_unique');
            $table->index('driver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movvi_charge_entries');
        Schema::dropIfExists('movvi_charge_imports');
    }
};
