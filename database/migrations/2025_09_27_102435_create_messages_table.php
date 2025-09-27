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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('players')->onDelete('set null');
            $table->foreignId('recipient_id')->constrained('players')->onDelete('cascade');
            $table->string('subject');
            $table->text('content');
            $table->enum('type', ['private', 'system', 'alliance', 'public', 'announcement']);
            $table->enum('priority', ['low', 'normal', 'high', 'urgent']);
            $table->enum('status', ['unread', 'read', 'archived', 'deleted']);
            $table->json('attachments')->nullable(); // Attached resources, items, etc.
            $table->timestamp('read_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_important')->default(false);
            $table->timestamps();
            
            $table->index(['recipient_id', 'status']);
            $table->index(['sender_id', 'type']);
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
        Schema::dropIfExists('messages');
    }
};
