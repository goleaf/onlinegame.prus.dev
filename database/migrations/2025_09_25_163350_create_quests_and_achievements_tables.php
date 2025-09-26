<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Quests table
        Schema::create('quests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description');
            $table->text('instructions')->nullable();
            $table->enum('category', ['tutorial', 'building', 'combat', 'exploration', 'trade', 'alliance', 'special']);
            $table->integer('difficulty')->default(1);  // 1-5 difficulty level
            $table->json('requirements')->nullable();  // JSON for quest requirements
            $table->json('rewards')->nullable();  // JSON for quest rewards
            $table->integer('experience_reward')->default(0);
            $table->integer('gold_reward')->default(0);
            $table->json('resource_rewards')->nullable();  // JSON for resource rewards
            $table->boolean('is_repeatable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'difficulty']);
            $table->index(['is_active', 'is_repeatable']);
        });

        // Player quests table
        Schema::create('player_quests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('quest_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['available', 'in_progress', 'completed', 'failed', 'expired'])->default('available');
            $table->integer('progress')->default(0);
            $table->json('progress_data')->nullable();  // JSON for quest progress tracking
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'quest_id']);
            $table->index(['player_id', 'status']);
            $table->index(['quest_id', 'status']);
        });

        // Achievements table
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description');
            $table->enum('category', ['building', 'combat', 'exploration', 'trade', 'alliance', 'special', 'milestone']);
            $table->integer('points')->default(0);  // Achievement points
            $table->json('requirements')->nullable();  // JSON for achievement requirements
            $table->json('rewards')->nullable();  // JSON for achievement rewards
            $table->string('icon')->nullable();  // Icon path
            $table->boolean('is_hidden')->default(false);  // Hidden achievements
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'points']);
            $table->index(['is_active', 'is_hidden']);
        });

        // Player achievements table
        Schema::create('player_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained()->onDelete('cascade');
            $table->timestamp('unlocked_at');
            $table->json('progress_data')->nullable();  // JSON for achievement progress
            $table->timestamps();

            $table->unique(['player_id', 'achievement_id']);
            $table->index(['player_id', 'unlocked_at']);
        });

        // Player statistics table
        Schema::create('player_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->integer('total_attack_points')->default(0);
            $table->integer('total_defense_points')->default(0);
            $table->integer('total_robber_points')->default(0);
            $table->integer('week_attack_points')->default(0);
            $table->integer('week_defense_points')->default(0);
            $table->integer('week_robber_points')->default(0);
            $table->integer('total_battles_won')->default(0);
            $table->integer('total_battles_lost')->default(0);
            $table->integer('total_units_killed')->default(0);
            $table->integer('total_units_lost')->default(0);
            $table->bigInteger('total_resources_raided')->default(0);
            $table->bigInteger('total_resources_lost')->default(0);
            $table->integer('total_buildings_built')->default(0);
            $table->integer('total_technologies_researched')->default(0);
            $table->integer('total_units_trained')->default(0);
            $table->integer('experience_points')->default(0);
            $table->integer('level')->default(1);
            $table->timestamps();

            $table->unique('player_id');
            $table->index(['total_attack_points', 'total_defense_points']);
            $table->index(['week_attack_points', 'week_defense_points']);
        });

        // Player notes table
        Schema::create('player_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('target_player_id')->constrained('players')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('color', ['red', 'yellow', 'green', 'blue', 'purple', 'orange'])->default('yellow');
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['player_id', 'target_player_id']);
            $table->index(['target_player_id', 'is_public']);
        });

        // Reports table for battle reports and notifications
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->onDelete('cascade');
            $table->foreignId('attacker_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('defender_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('from_village_id')->constrained('villages')->onDelete('cascade');
            $table->foreignId('to_village_id')->constrained('villages')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['attack', 'defense', 'support', 'spy', 'trade', 'system'])->default('attack');
            $table->enum('status', ['victory', 'defeat', 'draw', 'pending'])->default('pending');
            $table->json('battle_data')->nullable();  // JSON for battle statistics
            $table->json('attachments')->nullable();  // JSON for report attachments
            $table->boolean('is_read')->default(false);
            $table->boolean('is_important')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['world_id', 'attacker_id']);
            $table->index(['world_id', 'defender_id']);
            $table->index(['world_id', 'type']);
            $table->index(['world_id', 'status']);
            $table->index(['world_id', 'is_read']);
            $table->index(['world_id', 'is_important']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('player_notes');
        Schema::dropIfExists('player_statistics');
        Schema::dropIfExists('player_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('player_quests');
        Schema::dropIfExists('quests');
    }
};
