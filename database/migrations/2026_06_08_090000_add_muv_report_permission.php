<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(
            ['title' => 'muv_report_access'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        if (!Schema::hasTable('roles') || !Schema::hasTable('permission_role')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('title', 'muv_report_access')
            ->value('id');

        if (!$permissionId) {
            return;
        }

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

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('title', 'muv_report_access')
            ->value('id');

        if ($permissionId && Schema::hasTable('permission_role')) {
            DB::table('permission_role')
                ->where('permission_id', $permissionId)
                ->delete();
        }

        DB::table('permissions')
            ->where('title', 'muv_report_access')
            ->delete();
    }
};
