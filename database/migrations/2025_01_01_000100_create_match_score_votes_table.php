<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_score_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_score_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('vote', ['approve', 'dispute']);
            $table->unsignedSmallInteger('submitted_score_a')->nullable();
            $table->unsignedSmallInteger('submitted_score_b')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['match_score_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_score_votes');
    }
};
