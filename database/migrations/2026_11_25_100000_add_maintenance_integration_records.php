<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_integration_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('kind', 16);
            $table->string('external_reference', 80);
            $table->unsignedBigInteger('record_id');
            $table->char('creation_hash', 64);
            $table->unique(['company_id', 'kind', 'external_reference'], 'maintenance_integration_reference_unique');
        });

        Schema::table('vehicle_expenses', function (Blueprint $table) {
            $table->string('document_number', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_expenses', function (Blueprint $table) {
            $table->dropColumn('document_number');
        });
        Schema::dropIfExists('maintenance_integration_records');
    }
};
