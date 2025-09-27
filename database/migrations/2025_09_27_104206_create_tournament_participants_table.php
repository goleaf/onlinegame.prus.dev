<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->onDelete('cascade');
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->enum('status', ['registered', 'active', 'eliminated', 'winner', 'disqualified']);
            $table->integer('score')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->json('stats')->nullable(); // Tournament-specific statistics
            $table->json('rewards')->nullable(); // Rewards earned
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('eliminated_at')->nullable();
            $table->integer('final_rank')->nullable();
            $table->timestamps();
            
            $table->unique(['tournament_id', 'player_id']);
            $table->index(['tournament_id', 'status']);
            $table->index(['player_id', 'status']);
            $table->index(['final_rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_participants');
    }
};
