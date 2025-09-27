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
        // Only create tables that don't exist
        if (!Schema::hasTable('alliance_messages')) {
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
        }

        if (!Schema::hasTable('alliance_logs')) {
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

        // Add columns to existing alliance_diplomacy table if they don't exist
        if (Schema::hasTable('alliance_diplomacy')) {
            Schema::table('alliance_diplomacy', function (Blueprint $table) {
                if (!Schema::hasColumn('alliance_diplomacy', 'message')) {
                    $table->text('message')->nullable();
                }
                if (!Schema::hasColumn('alliance_diplomacy', 'proposed_at')) {
                    $table->timestamp('proposed_at')->nullable();
                }
                if (!Schema::hasColumn('alliance_diplomacy', 'responded_at')) {
                    $table->timestamp('responded_at')->nullable();
                }
                if (!Schema::hasColumn('alliance_diplomacy', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable();
                }
                if (!Schema::hasColumn('alliance_diplomacy', 'terms')) {
                    $table->json('terms')->nullable();
                }
            });
        }

        // Add columns to existing alliance_wars table if they don't exist
        if (Schema::hasTable('alliance_wars')) {
            Schema::table('alliance_wars', function (Blueprint $table) {
                if (!Schema::hasColumn('alliance_wars', 'declaration_message')) {
                    $table->text('declaration_message')->nullable();
                }
                if (!Schema::hasColumn('alliance_wars', 'declared_at')) {
                    $table->timestamp('declared_at')->nullable();
                }
                if (!Schema::hasColumn('alliance_wars', 'started_at')) {
                    $table->timestamp('started_at')->nullable();
                }
                if (!Schema::hasColumn('alliance_wars', 'ended_at')) {
                    $table->timestamp('ended_at')->nullable();
                }
                if (!Schema::hasColumn('alliance_wars', 'war_stats')) {
                    $table->json('war_stats')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alliance_logs');
        Schema::dropIfExists('alliance_messages');
        
        // Remove added columns from existing tables
        if (Schema::hasTable('alliance_diplomacy')) {
            Schema::table('alliance_diplomacy', function (Blueprint $table) {
                $table->dropColumn(['message', 'proposed_at', 'responded_at', 'expires_at', 'terms']);
            });
        }

        if (Schema::hasTable('alliance_wars')) {
            Schema::table('alliance_wars', function (Blueprint $table) {
                $table->dropColumn(['declaration_message', 'declared_at', 'started_at', 'ended_at', 'war_stats']);
            });
        }
    }
};
