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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['info', 'warning', 'success', 'error', 'achievement', 'battle', 'trade', 'diplomacy', 'artifact']);
            $table->enum('priority', ['low', 'normal', 'high', 'urgent']);
            $table->enum('status', ['unread', 'read', 'dismissed']);
            $table->json('data')->nullable(); // Additional data (links, actions, etc.)
            $table->string('icon')->nullable(); // Icon identifier
            $table->string('action_url')->nullable(); // URL for click action
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_persistent')->default(false); // Survives page refresh
            $table->boolean('is_auto_dismiss')->default(true); // Auto-dismiss after time
            $table->integer('auto_dismiss_seconds')->default(5); // Auto-dismiss delay
            $table->timestamps();
            
            $table->index(['player_id', 'status']);
            $table->index(['type', 'priority']);
            $table->index(['read_at']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
