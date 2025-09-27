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
        Schema::create('alliance_diplomacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained()->onDelete('cascade');
            $table->foreignId('target_alliance_id')->constrained('alliances')->onDelete('cascade');
            $table->enum('status', ['neutral', 'ally', 'enemy', 'non_aggression_pact', 'trade_agreement']);
            $table->enum('proposed_by', ['alliance', 'target_alliance']);
            $table->enum('response_status', ['pending', 'accepted', 'declined', 'cancelled'])->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('proposed_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('terms')->nullable();
            $table->timestamps();

            $table->unique(['alliance_id', 'target_alliance_id']);
            $table->index(['alliance_id', 'status']);
            $table->index(['target_alliance_id', 'status']);
            $table->index(['response_status']);
        });

        Schema::create('alliance_wars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attacker_alliance_id')->constrained('alliances')->onDelete('cascade');
            $table->foreignId('defender_alliance_id')->constrained('alliances')->onDelete('cascade');
            $table->enum('status', ['declared', 'active', 'ended', 'cancelled']);
            $table->text('declaration_message')->nullable();
            $table->timestamp('declared_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->json('war_stats')->nullable(); // battles, casualties, etc.
            $table->timestamps();

            $table->index(['attacker_alliance_id', 'status']);
            $table->index(['defender_alliance_id', 'status']);
            $table->index(['status']);
        });

        Schema::create('alliance_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('players')->onDelete('cascade');
            $table->enum('type', ['announcement', 'general', 'war', 'diplomacy', 'leadership']);
            $table->string('title');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_important')->default(false);
            $table->json('read_by')->nullable(); // array of player IDs who read the message
            $table->timestamps();

            $table->index(['alliance_id', 'type']);
            $table->index(['alliance_id', 'created_at']);
            $table->index(['is_pinned', 'is_important']);
        });

        Schema::create('alliance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alliance_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('action', [
                'member_joined', 'member_left', 'member_kicked', 'member_promoted', 'member_demoted',
                'alliance_created', 'alliance_disbanded', 'alliance_renamed', 'alliance_description_changed',
                'diplomacy_proposed', 'diplomacy_accepted', 'diplomacy_declined', 'diplomacy_cancelled',
                'war_declared', 'war_ended', 'message_posted', 'settings_changed'
            ]);
            $table->text('description');
            $table->json('data')->nullable(); // additional data about the action
            $table->timestamps();

            $table->index(['alliance_id', 'action']);
            $table->index(['alliance_id', 'created_at']);
            $table->index(['player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_logs');
        Schema::dropIfExists('alliance_messages');
        Schema::dropIfExists('alliance_wars');
        Schema::dropIfExists('alliance_diplomacy');
    }
};
