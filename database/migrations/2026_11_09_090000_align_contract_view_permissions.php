<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('permission_role')) {
            return;
        }

        $createPermissionId = DB::table('permissions')
            ->where('title', 'admin_contract_create')
            ->whereNull('deleted_at')
            ->value('id');
        $contractPermissionIds = DB::table('permissions')
            ->whereIn('title', ['admin_contract_access', 'admin_contract_create', 'admin_contract_show'])
            ->whereNull('deleted_at')
            ->pluck('id');

        if (! $createPermissionId || $contractPermissionIds->isEmpty()) {
            return;
        }

        $roleIds = DB::table('permission_role')
            ->where('permission_id', $createPermissionId)
            ->pluck('role_id')
            ->merge(DB::table('roles')->whereIn('title', ['Gestor', 'Manager'])->pluck('id'))
            ->unique();

        foreach ($roleIds as $roleId) {
            foreach ($contractPermissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Permission alignment is intentionally not revoked on rollback.
    }
};
