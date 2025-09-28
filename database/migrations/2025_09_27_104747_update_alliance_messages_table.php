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
        Schema::table('alliance_messages', function (Blueprint $table) {
            // Rename columns to match new model structure
            if (Schema::hasColumn('alliance_messages', 'type')) {
                $table->renameColumn('type', 'message_type');
            }
            if (Schema::hasColumn('alliance_messages', 'title')) {
                $table->renameColumn('title', 'subject');
            }
            if (Schema::hasColumn('alliance_messages', 'content')) {
                $table->renameColumn('content', 'body');
            }
            if (Schema::hasColumn('alliance_messages', 'is_important')) {
                $table->renameColumn('is_important', 'is_announcement');
            }

            // Add new columns
            if (! Schema::hasColumn('alliance_messages', 'priority')) {
                $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('body');
            }
            if (! Schema::hasColumn('alliance_messages', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('is_announcement');
            }
            if (! Schema::hasColumn('alliance_messages', 'reference_number')) {
                $table->string('reference_number', 50)->unique()->nullable()->after('expires_at');
            }

            // Remove old columns that are no longer needed
            if (Schema::hasColumn('alliance_messages', 'read_by')) {
                $table->dropColumn('read_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alliance_messages', function (Blueprint $table) {
            // Revert column renames
            if (Schema::hasColumn('alliance_messages', 'message_type')) {
                $table->renameColumn('message_type', 'type');
            }
            if (Schema::hasColumn('alliance_messages', 'subject')) {
                $table->renameColumn('subject', 'title');
            }
            if (Schema::hasColumn('alliance_messages', 'body')) {
                $table->renameColumn('body', 'content');
            }
            if (Schema::hasColumn('alliance_messages', 'is_announcement')) {
                $table->renameColumn('is_announcement', 'is_important');
            }

            // Remove new columns
            $table->dropColumn(['priority', 'expires_at', 'reference_number']);

            // Add back old columns
            $table->json('read_by')->nullable();
        });
    }
};
