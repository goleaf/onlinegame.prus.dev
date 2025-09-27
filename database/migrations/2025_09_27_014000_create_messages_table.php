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
            $table->foreignId('recipient_id')->nullable()->constrained('players')->onDelete('cascade');
            $table->foreignId('alliance_id')->nullable()->constrained('alliances')->onDelete('cascade');
            $table->string('subject');
            $table->text('body');
            $table->enum('message_type', ['private', 'alliance', 'system', 'battle_report', 'trade_offer', 'diplomacy'])->default('private');
            $table->boolean('is_read')->default(false);
            $table->boolean('is_deleted_by_sender')->default(false);
            $table->boolean('is_deleted_by_recipient')->default(false);
            $table->foreignId('parent_message_id')->nullable()->constrained('messages')->onDelete('cascade');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->timestamp('expires_at')->nullable();
            $table->string('reference_number')->unique()->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['recipient_id', 'is_read', 'is_deleted_by_recipient']);
            $table->index(['sender_id', 'is_deleted_by_sender']);
            $table->index(['alliance_id', 'message_type']);
            $table->index(['message_type', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index('expires_at');
            $table->index('reference_number');
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
