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
        Schema::create('artifact_effects', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->nullable()->unique();
            $table->foreignId('artifact_id')->constrained('artifacts')->onDelete('cascade');
            $table->string('effect_type'); // resource_bonus, combat_bonus, etc.
            $table->string('target_type')->nullable(); // player, village, server, tribe, alliance
            $table->unsignedBigInteger('target_id')->nullable(); // ID of the target
            $table->json('effect_data')->nullable(); // Additional effect data
            $table->decimal('magnitude', 10, 2); // The magnitude of the effect
            $table->string('duration_type')->default('permanent'); // permanent, temporary, conditional
            $table->integer('duration_hours')->nullable(); // Duration in hours for temporary effects
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            
            $table->index(['artifact_id', 'is_active']);
            $table->index(['effect_type', 'is_active']);
            $table->index(['target_type', 'target_id']);
            $table->index(['expires_at']);
            $table->index(['starts_at']);
            $table->index(['duration_type']);
            $table->index(['reference_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artifact_effects');
    }
};