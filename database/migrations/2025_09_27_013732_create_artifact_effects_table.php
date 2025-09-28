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
        Schema::create('artifact_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artifact_id')->constrained('artifacts')->onDelete('cascade');
            $table->string('effect_type'); // resource_bonus, combat_bonus, building_bonus, etc.
            $table->string('target_type'); // player, village, server, tribe
            $table->string('target_id')->nullable(); // Specific target ID
            $table->json('effect_data'); // Effect parameters and values
            $table->decimal('magnitude', 8, 2); // Effect strength
            $table->enum('duration_type', ['permanent', 'temporary', 'conditional']); // Effect duration
            $table->integer('duration_hours')->nullable(); // Duration in hours
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['artifact_id', 'is_active']);
            $table->index(['effect_type', 'target_type']);
            $table->index(['expires_at']);
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
