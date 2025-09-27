<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Game configuration table
        Schema::create('game_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string');  // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Alliances table
        Schema::create('alliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained()->onDelete('cascade');
            $table->string('tag', 8)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('leader_id')->constrained('players')->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->integer('villages_count')->default(0);
            $table->integer('members_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['world_id', 'points']);
            $table->index(['world_id', 'members_count']);
        });

        // Alliance members table
        Schema::create('alliance_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->enum('rank', ['member', 'elder', 'leader'])->default('member');
            $table->timestamp('joined_at');
            $table->timestamps();

            $table->unique('player_id');
            $table->index(['alliance_id', 'rank']);
        });

        // Building types table
        Schema::create('building_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->text('description')->nullable();
            $table->integer('max_level')->default(20);
            $table->json('requirements')->nullable();  // JSON for building requirements
            $table->json('costs')->nullable();  // JSON for upgrade costs
            $table->json('production')->nullable();  // JSON for production rates
            $table->json('population')->nullable();  // JSON for population requirements
            $table->boolean('is_special')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('key');
            $table->index(['is_active', 'is_special']);
        });

        // Unit types table
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->enum('tribe', ['roman', 'teuton', 'gaul', 'natars']);
            $table->text('description')->nullable();
            $table->integer('attack')->default(0);
            $table->integer('defense_infantry')->default(0);
            $table->integer('defense_cavalry')->default(0);
            $table->integer('speed')->default(0);
            $table->integer('carry_capacity')->default(0);
            $table->json('costs')->nullable();  // JSON for training costs
            $table->json('requirements')->nullable();  // JSON for training requirements
            $table->boolean('is_special')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'tribe']);
            $table->index(['tribe', 'is_active']);
        });

        // Technologies table
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->text('description')->nullable();
            $table->json('requirements')->nullable();  // JSON for research requirements
            $table->json('costs')->nullable();  // JSON for research costs
            $table->json('effects')->nullable();  // JSON for technology effects
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('key');
            $table->index('is_active');
        });

        // Player technologies table
        Schema::create('player_technologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('technology_id')->constrained()->onDelete('cascade');
            $table->integer('level')->default(0);
            $table->timestamp('researched_at')->nullable();
            $table->timestamps();

            $table->unique(['player_id', 'technology_id']);
            $table->index(['player_id', 'level']);
        });

        // Tasks table for game tasks
        Schema::create('game_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('village_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('category', ['building', 'research', 'training', 'exploration', 'combat', 'trade', 'general']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('progress')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();  // JSON for additional task data
            $table->timestamps();

            $table->index(['player_id', 'status']);
            $table->index(['village_id', 'status']);
            $table->index(['category', 'status']);
            $table->index(['due_at']);
        });

        // Game events table for logging
        Schema::create('game_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('village_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type');
            $table->string('event_subtype')->nullable();
            $table->text('description');
            $table->json('data')->nullable();  // JSON for event data
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['player_id', 'event_type']);
            $table->index(['village_id', 'event_type']);
            $table->index(['event_type', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('battles');
        Schema::dropIfExists('movements');
        Schema::dropIfExists('troops');
        Schema::dropIfExists('resource_production_logs');
        Schema::dropIfExists('training_queues');
        Schema::dropIfExists('building_queues');
        Schema::dropIfExists('player_achievements');
        Schema::dropIfExists('player_quests');
        Schema::dropIfExists('player_notes');
        Schema::dropIfExists('player_statistics');
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('game_tasks');
        Schema::dropIfExists('player_technologies');
        Schema::dropIfExists('technologies');
        Schema::dropIfExists('unit_types');
        Schema::dropIfExists('building_types');
        Schema::dropIfExists('alliance_members');
        Schema::dropIfExists('alliances');
        Schema::dropIfExists('game_configs');
    }
};
