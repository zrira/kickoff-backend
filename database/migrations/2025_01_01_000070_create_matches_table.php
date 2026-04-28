<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['waiting', 'lobby', 'active', 'scoring', 'completed', 'cancelled'])
                  ->default('waiting');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('lobby_expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sport_id', 'city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
