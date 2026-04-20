<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_favorites')) {
            return;
        }

        Schema::table('user_favorites', function (Blueprint $table) {
            if (!Schema::hasColumn('user_favorites', 'order')) {
                $table->unsignedInteger('order')->nullable()->after('icon');
            }
        });

        if (Schema::hasColumn('user_favorites', 'sort_order')) {
            DB::statement('UPDATE user_favorites SET `order` = sort_order WHERE `order` IS NULL');
        }

        Schema::table('user_favorites', function (Blueprint $table) {
            $drops = [];

            foreach (['route_name', 'route_params', 'active_pattern', 'sort_order'] as $column) {
                if (Schema::hasColumn('user_favorites', $column)) {
                    $drops[] = $column;
                }
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_favorites')) {
            return;
        }

        Schema::table('user_favorites', function (Blueprint $table) {
            if (!Schema::hasColumn('user_favorites', 'route_name')) {
                $table->string('route_name')->nullable()->after('url');
            }

            if (!Schema::hasColumn('user_favorites', 'route_params')) {
                $table->json('route_params')->nullable()->after('route_name');
            }

            if (!Schema::hasColumn('user_favorites', 'active_pattern')) {
                $table->string('active_pattern')->nullable()->after('route_params');
            }

            if (!Schema::hasColumn('user_favorites', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('icon');
            }
        });

        if (Schema::hasColumn('user_favorites', 'order') && Schema::hasColumn('user_favorites', 'sort_order')) {
            DB::statement('UPDATE user_favorites SET sort_order = `order`');
        }
    }
};
