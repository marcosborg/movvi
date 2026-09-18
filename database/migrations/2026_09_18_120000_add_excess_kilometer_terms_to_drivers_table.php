<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('weekly_km_limit', 10, 2)->default(2000)->after('pays_fuel');
            $table->decimal('excess_km_rate', 8, 4)->default(0.10)->after('weekly_km_limit');
        });

        DB::table('drivers')->whereIn('name', [
            'Celso Cristiano',
            'Bruno Novo',
            'Henrique Bleasby',
            'Luiz Carlos Chaves',
        ])->update(['weekly_km_limit' => 2200]);

        DB::table('drivers')->whereIn('name', [
            'Celso Cristiano',
            'Luiz Carlos Chaves',
            'Marcelo Capeleiro Verde',
            'Carlos Ribeiro Azevedo',
            'Filipe Gomes Correia',
            'Lucas Ramos',
            'Joao Luiz Souza',
            'Vitor de Barros',
            'Antonio Telinhos',
            'Jose Miguel Beca',
        ])->update(['excess_km_rate' => 0.05]);
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['weekly_km_limit', 'excess_km_rate']);
        });
    }
};
