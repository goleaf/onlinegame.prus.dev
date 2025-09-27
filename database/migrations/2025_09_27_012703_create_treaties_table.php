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
        Schema::create('treaties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['non_aggression', 'alliance', 'trade', 'military_support', 'peace', 'war']);
            $table->enum('status', ['proposed', 'active', 'expired', 'cancelled', 'violated']);
            $table->foreignId('proposer_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('players')->onDelete('cascade');
            $table->json('terms')->nullable(); // Custom terms and conditions
            $table->json('benefits')->nullable(); // Benefits for each party
            $table->json('penalties')->nullable(); // Penalties for violation
            $table->timestamp('proposed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('duration_hours')->default(24); // Treaty duration
            $table->boolean('is_public')->default(false); // Public or private treaty
            $table->timestamps();
            
            $table->index(['proposer_id', 'status']);
            $table->index(['recipient_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treaties');
    }
};
