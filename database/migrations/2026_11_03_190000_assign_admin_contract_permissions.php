<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'admin_contract_access',
        'admin_contract_create',
        'admin_contract_show',
        'admin_contract_edit',
        'admin_contract_delete',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('title', self::PERMISSIONS)
            ->pluck('id');

        $adminRoleIds = DB::table('roles')
            ->whereIn('title', ['Admin', 'Administrador'])
            ->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles') || !Schema::hasTable('permission_role')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('title', self::PERMISSIONS)
            ->pluck('id');

        $adminRoleIds = DB::table('roles')
            ->whereIn('title', ['Admin', 'Administrador'])
            ->pluck('id');

        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->whereIn('role_id', $adminRoleIds)
            ->delete();
    }
};
