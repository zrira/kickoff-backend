<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elo_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->integer('points_before');
            $table->integer('points_after');
            $table->integer('delta');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'sport_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elo_history');
    }
};
