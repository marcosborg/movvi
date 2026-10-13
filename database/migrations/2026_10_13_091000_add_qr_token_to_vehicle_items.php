<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_items', function (Blueprint $table) {
            $table->string('qr_token', 64)->nullable()->unique()->after('license_plate');
        });

        DB::table('vehicle_items')->orderBy('id')->eachById(function ($vehicle) {
            DB::table('vehicle_items')->where('id', $vehicle->id)->update(['qr_token' => Str::random(48)]);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_items', fn (Blueprint $table) => $table->dropColumn('qr_token'));
    }
};
