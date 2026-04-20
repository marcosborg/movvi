<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('url');
            $table->string('route_name')->nullable();
            $table->json('route_params')->nullable();
            $table->string('active_pattern')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'sort_order']);
            $table->unique(['user_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorites');
    }
};
