<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->integer('elo_points')->default(1000);
            $table->unsignedInteger('rank')->nullable();
            $table->unsignedInteger('total_matches')->default(0);
            $table->unsignedInteger('total_wins')->default(0);
            $table->unsignedSmallInteger('trust_score')->default(100);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'sport_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};
