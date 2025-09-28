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
        Schema::create('alliance_wars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attacker_alliance_id')->constrained('alliances')->onDelete('cascade');
            $table->foreignId('defender_alliance_id')->constrained('alliances')->onDelete('cascade');
            $table->string('reason')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamp('declared_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('war_score')->default(0);
            $table->text('war_data')->nullable(); // JSON data for war statistics
            $table->timestamps();

            $table->index(['attacker_alliance_id', 'status']);
            $table->index(['defender_alliance_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_wars');
    }
};
