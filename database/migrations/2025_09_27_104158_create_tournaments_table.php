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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['pvp', 'pve', 'raid', 'defense', 'speed', 'endurance', 'resource_race', 'building_contest']);
            $table->enum('format', ['single_elimination', 'double_elimination', 'round_robin', 'swiss', 'bracket', 'race']);
            $table->enum('status', ['upcoming', 'registration', 'active', 'completed', 'cancelled']);
            $table->integer('max_participants')->default(32);
            $table->integer('min_participants')->default(2);
            $table->integer('entry_fee')->default(0); // Gold cost
            $table->json('prizes')->nullable(); // Prize structure
            $table->json('rules')->nullable(); // Tournament-specific rules
            $table->json('requirements')->nullable(); // Entry requirements
            $table->timestamp('registration_start')->nullable();
            $table->timestamp('registration_end')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('round_duration_minutes')->default(60); // Time per round
            $table->boolean('is_public')->default(true);
            $table->boolean('allow_spectators')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'status']);
            $table->index(['status', 'start_time']);
            $table->index(['registration_start', 'registration_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
