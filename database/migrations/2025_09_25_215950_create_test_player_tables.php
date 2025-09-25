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
        // Only create these tables if they don't exist (for testing)
        if (!Schema::hasTable('player_tasks')) {
            Schema::create('player_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained()->onDelete('cascade');
                $table->foreignId('player_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->text('description');
                $table->enum('type', ['building', 'troop', 'resource', 'battle', 'trade', 'exploration'])->default('building');
                $table->enum('status', ['available', 'active', 'completed', 'expired'])->default('available');
                $table->integer('progress')->default(0);
                $table->integer('target')->default(1);
                $table->json('rewards')->nullable();
                $table->timestamp('deadline')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'player_id']);
                $table->index(['world_id', 'status']);
                $table->index(['world_id', 'type']);
                $table->index(['deadline']);
            });
        }

        if (!Schema::hasTable('player_quests')) {
            Schema::create('player_quests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained()->onDelete('cascade');
                $table->foreignId('player_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->text('description');
                $table->enum('type', ['main', 'side', 'daily', 'weekly', 'special'])->default('main');
                $table->enum('status', ['available', 'active', 'completed', 'expired'])->default('available');
                $table->integer('progress')->default(0);
                $table->integer('target')->default(1);
                $table->json('rewards')->nullable();
                $table->json('requirements')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'player_id']);
                $table->index(['world_id', 'status']);
                $table->index(['world_id', 'type']);
            });
        }

        if (!Schema::hasTable('player_achievements')) {
            Schema::create('player_achievements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained()->onDelete('cascade');
                $table->foreignId('player_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->text('description');
                $table->enum('type', ['building', 'troop', 'battle', 'resource', 'exploration', 'social'])->default('building');
                $table->enum('status', ['available', 'unlocked'])->default('available');
                $table->integer('progress')->default(0);
                $table->integer('target')->default(1);
                $table->json('rewards')->nullable();
                $table->json('requirements')->nullable();
                $table->timestamp('unlocked_at')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'player_id']);
                $table->index(['world_id', 'status']);
                $table->index(['world_id', 'type']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_achievements');
        Schema::dropIfExists('player_quests');
        Schema::dropIfExists('player_tasks');
    }
};