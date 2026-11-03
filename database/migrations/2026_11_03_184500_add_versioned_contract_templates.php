<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contract_templates')) {
            Schema::create('contract_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedInteger('version')->default(1);
                $table->longText('content');
                $table->boolean('is_active')->default(false)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['name', 'version']);
            });
        }

        Schema::table('admin_contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('admin_contracts', 'contract_template_id')) {
                $table->foreignId('contract_template_id')->nullable()->after('driver_id')
                    ->constrained('contract_templates')->nullOnDelete();
            }
            if (! Schema::hasColumn('admin_contracts', 'template_version')) {
                $table->unsignedInteger('template_version')->nullable()->after('contract_template_id');
            }
            if (! Schema::hasColumn('admin_contracts', 'source_content')) {
                $table->longText('source_content')->nullable()->after('template_version');
            }
            if (! Schema::hasColumn('admin_contracts', 'rendered_content')) {
                $table->longText('rendered_content')->nullable()->after('source_content');
            }
            if (! Schema::hasColumn('admin_contracts', 'data_snapshot')) {
                $table->longText('data_snapshot')->nullable()->after('rendered_content');
            }
            if (! Schema::hasColumn('admin_contracts', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('data_snapshot');
            }
            if (! Schema::hasColumn('admin_contracts', 'generated_by')) {
                $table->foreignId('generated_by')->nullable()->after('generated_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_contracts', function (Blueprint $table) {
            foreach (['generated_by', 'contract_template_id'] as $column) {
                if (Schema::hasColumn('admin_contracts', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            foreach (['template_version', 'source_content', 'rendered_content', 'data_snapshot', 'generated_at'] as $column) {
                if (Schema::hasColumn('admin_contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('contract_templates');
    }
};
