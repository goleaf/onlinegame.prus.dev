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
        Schema::create('chat_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('channel_type', ['global', 'alliance', 'private', 'trade', 'diplomacy', 'custom'])->default('custom');
            $table->foreignId('alliance_id')->nullable()->constrained('alliances')->onDelete('cascade');
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('max_members')->nullable();
            $table->foreignId('created_by')->constrained('players')->onDelete('cascade');
            $table->json('settings')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['channel_type', 'is_active']);
            $table->index(['alliance_id', 'channel_type']);
            $table->index(['is_public', 'is_active']);
            $table->index('created_by');
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
