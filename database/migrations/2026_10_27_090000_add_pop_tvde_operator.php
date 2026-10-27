<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tvde_operators')) {
            return;
        }

        $existingId = DB::table('tvde_operators')
            ->whereRaw('LOWER(name) = ?', ['pop'])
            ->value('id');

        if ($existingId) {
            DB::table('tvde_operators')->where('id', $existingId)->update([
                'name' => 'POP',
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('tvde_operators')->insert([
                'name' => 'POP',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('tvde_operators')) {
            return;
        }

        $popIds = DB::table('tvde_operators')
            ->where('name', 'POP')
            ->pluck('id');

        if ($popIds->isEmpty()) {
            return;
        }

        $isUsed = Schema::hasTable('tvde_activities')
            && DB::table('tvde_activities')->whereIn('tvde_operator_id', $popIds)->exists();

        if (!$isUsed) {
            DB::table('tvde_operators')->whereIn('id', $popIds)->delete();
        }
    }
};
