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
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->foreignId('building_type_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->integer('level')->default(1);
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('upgrade_started_at')->nullable();
            $table->timestamp('upgrade_completed_at')->nullable();
            $table->json('metadata')->nullable();  // JSON for additional building data
            $table->timestamps();

            $table->index(['village_id', 'building_type_id']);
            $table->index(['village_id', 'level']);
            $table->index(['village_id', 'is_active']);
            $table->unique(['village_id', 'x', 'y']);  // Prevent overlapping buildings
        });

        // Building queues for upgrades
        Schema::create('building_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->foreignId('building_id')->constrained()->onDelete('cascade');
            $table->integer('target_level');
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
            $table->json('costs')->nullable();  // JSON for upgrade costs
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['village_id', 'status']);
            $table->index(['completed_at']);
        });

        // Troops table
        Schema::create('troops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_type_id')->constrained()->onDelete('cascade');
            $table->integer('count')->default(0);
            $table->integer('in_village')->default(0);  // Troops currently in village
            $table->integer('in_attack')->default(0);  // Troops currently attacking
            $table->integer('in_defense')->default(0);  // Troops currently defending
            $table->integer('in_support')->default(0);  // Troops currently supporting
            $table->timestamps();

            $table->unique(['village_id', 'unit_type_id']);
            $table->index(['village_id', 'count']);
        });

        // Training queues
        Schema::create('training_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->foreignId('unit_type_id')->constrained()->onDelete('cascade');
            $table->integer('count');
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
            $table->json('costs')->nullable();  // JSON for training costs
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['village_id', 'status']);
            $table->index(['completed_at']);
        });

        // Movements table for troop movements
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_village_id')->constrained('villages')->onDelete('cascade');
            $table->foreignId('to_village_id')->constrained('villages')->onDelete('cascade');
            $table->enum('type', ['attack', 'support', 'spy', 'trade', 'return']);
            $table->json('troops')->nullable();  // JSON for troop composition
            $table->json('resources')->nullable();  // JSON for resource amounts
            $table->timestamp('started_at');
            $table->timestamp('arrives_at');
            $table->timestamp('returned_at')->nullable();
            $table->enum('status', ['travelling', 'arrived', 'returning', 'completed', 'cancelled'])->default('travelling');
            $table->json('metadata')->nullable();  // JSON for additional movement data
            $table->timestamps();

            $table->index(['player_id', 'type']);
            $table->index(['from_village_id', 'status']);
            $table->index(['to_village_id', 'status']);
            $table->index(['arrives_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
        Schema::dropIfExists('training_queues');
        Schema::dropIfExists('troops');
        Schema::dropIfExists('building_queues');
        Schema::dropIfExists('buildings');
    }
};
