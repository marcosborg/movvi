<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receipts')) {
            Schema::table('receipts', function (Blueprint $table) {
                if (!Schema::hasColumn('receipts', 'submitted_by_admin')) {
                    $table->boolean('submitted_by_admin')->default(false)->after('tvde_week_id');
                }

                if (!Schema::hasColumn('receipts', 'processed_as')) {
                    $table->string('processed_as')->nullable()->after('submitted_by_admin');
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(
                ['title' => 'force_driver_receipt_submission'],
                ['created_at' => now(), 'updated_at' => now()]
            );

            if (Schema::hasTable('roles') && Schema::hasTable('permission_role')) {
                $permissionId = DB::table('permissions')
                    ->where('title', 'force_driver_receipt_submission')
                    ->value('id');

                $adminRoleIds = DB::table('roles')
                    ->whereIn('title', ['Admin', 'Administrador'])
                    ->pluck('id');

                foreach ($adminRoleIds as $roleId) {
                    DB::table('permission_role')->updateOrInsert([
                        'permission_id' => $permissionId,
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('receipts')) {
            Schema::table('receipts', function (Blueprint $table) {
                if (Schema::hasColumn('receipts', 'processed_as')) {
                    $table->dropColumn('processed_as');
                }

                if (Schema::hasColumn('receipts', 'submitted_by_admin')) {
                    $table->dropColumn('submitted_by_admin');
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            if (Schema::hasTable('permission_role')) {
                $permissionId = DB::table('permissions')
                    ->where('title', 'force_driver_receipt_submission')
                    ->value('id');

                if ($permissionId) {
                    DB::table('permission_role')
                        ->where('permission_id', $permissionId)
                        ->delete();
                }
            }

            DB::table('permissions')
                ->where('title', 'force_driver_receipt_submission')
                ->delete();
        }
    }
};
