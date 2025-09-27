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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained('chat_channels')->onDelete('cascade');
            $table->enum('channel_type', ['global', 'alliance', 'private', 'trade', 'diplomacy'])->default('global');
            $table->text('message');
            $table->enum('message_type', ['text', 'system', 'announcement', 'emote', 'command'])->default('text');
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->string('reference_number')->unique()->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['channel_id', 'channel_type', 'created_at']);
            $table->index(['sender_id', 'created_at']);
            $table->index(['channel_type', 'created_at']);
            $table->index(['is_deleted', 'created_at']);
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};