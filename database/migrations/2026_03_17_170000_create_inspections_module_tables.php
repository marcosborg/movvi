<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->foreignId('vehicle_id')->constrained('vehicle_items')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->foreignId('previous_inspection_id')->nullable()->constrained('inspections')->nullOnDelete();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->string('location_text')->nullable();
            $table->decimal('location_accuracy', 8, 2)->nullable();
            $table->string('location_timezone', 60)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('extra_observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'created_at']);
            $table->index(['type', 'status']);
        });

        Schema::create('inspection_step_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->unsignedTinyInteger('step');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inspection_id', 'step']);
        });

        Schema::create('inspection_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->string('group_key', 50);
            $table->string('item_key', 80);
            $table->boolean('value_bool')->nullable();
            $table->integer('value_int')->nullable();
            $table->text('value_text')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'group_key', 'item_key']);
        });

        Schema::create('inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('slot', 60)->nullable();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 80)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['inspection_id', 'category']);
            $table->index(['inspection_id', 'slot']);
        });

        Schema::create('inspection_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->foreignId('origin_damage_id')->nullable()->constrained('inspection_damages')->nullOnDelete();
            $table->string('scope', 20)->default('exterior');
            $table->string('location', 30);
            $table->string('part', 120);
            $table->string('part_section', 120)->nullable();
            $table->string('damage_type', 40);
            $table->text('notes')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inspection_id', 'scope']);
            $table->index(['inspection_id', 'is_resolved']);
        });

        Schema::create('inspection_damage_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_id')->constrained('inspection_damages')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 80)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('inspection_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->string('role', 20);
            $table->string('signed_by_name');
            $table->string('signed_by_document')->nullable();
            $table->string('signature_path');
            $table->string('signature_hash', 64)->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();

            $table->unique(['inspection_id', 'role']);
        });

        Schema::create('inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->unique()->constrained('inspections')->cascadeOnDelete();
            $table->string('pdf_path');
            $table->string('pdf_hash', 64);
            $table->json('snapshot_json')->nullable();
            $table->timestamp('generated_at');
            $table->boolean('immutable')->default(true);
            $table->timestamps();
        });

        Schema::create('inspection_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('inspections')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_audits');
        Schema::dropIfExists('inspection_reports');
        Schema::dropIfExists('inspection_signatures');
        Schema::dropIfExists('inspection_damage_photos');
        Schema::dropIfExists('inspection_damages');
        Schema::dropIfExists('inspection_photos');
        Schema::dropIfExists('inspection_check_items');
        Schema::dropIfExists('inspection_step_states');
        Schema::dropIfExists('inspections');
    }
};
