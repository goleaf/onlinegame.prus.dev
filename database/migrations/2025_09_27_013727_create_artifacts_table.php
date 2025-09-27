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
        Schema::create('artifacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['weapon', 'armor', 'tool', 'mystical', 'relic', 'crystal']);
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary', 'mythic']);
            $table->enum('status', ['active', 'inactive', 'hidden', 'destroyed']);
            $table->foreignId('owner_id')->nullable()->constrained('players')->onDelete('set null');
            $table->foreignId('village_id')->nullable()->constrained('villages')->onDelete('set null');
            $table->json('effects')->nullable(); // Server-wide effects
            $table->json('requirements')->nullable(); // Requirements to activate
            $table->integer('power_level')->default(1); // 1-100
            $table->integer('durability')->default(100); // 0-100
            $table->integer('max_durability')->default(100);
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For temporary artifacts
            $table->boolean('is_server_wide')->default(false); // Affects entire server
            $table->boolean('is_unique')->default(false); // Only one can exist
            $table->timestamps();
            
            $table->index(['type', 'rarity']);
            $table->index(['status', 'is_server_wide']);
            $table->index(['owner_id', 'status']);
            $table->index(['village_id', 'status']);
            $table->index(['expires_at']);
            $table->index(['discovered_at']);
            $table->index(['activated_at']);
            $table->index(['power_level']);
            $table->index(['durability']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artifacts');
    }
};
