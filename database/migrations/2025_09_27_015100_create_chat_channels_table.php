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
        Schema::create('chat_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['global', 'alliance', 'private', 'trade', 'diplomacy'])->default('global');
            $table->text('description')->nullable();
            $table->foreignId('alliance_id')->nullable()->constrained('alliances')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('players')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->json('settings')->nullable();
            $table->string('reference_number')->unique()->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'is_active']);
            $table->index(['alliance_id', 'is_active']);
            $table->index(['is_public', 'is_active']);
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_channels');
    }
};
