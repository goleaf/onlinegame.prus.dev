<?php

namespace App\Console\Commands;

use App\Models\Game\Message;
use App\Services\MessageService;
use Illuminate\Console\Command;

class MessageCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:messages:cleanup
                            {--days=30 : Number of days to keep messages}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old and expired messages from the database';

    protected $messageService;

    public function __construct()
    {
        parent::__construct();
        $this->messageService = new MessageService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Message Cleanup Command');
        $this->newLine();

        $days = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Clean up expired messages
        $this->info('📅 Cleaning up expired messages...');
        $expiredCount = $this->cleanupExpiredMessages($isDryRun);
        $this->line("   Found {$expiredCount} expired messages");

        // Clean up old messages
        $this->info("📅 Cleaning up messages older than {$days} days...");
        $oldCount = $this->cleanupOldMessages($days, $isDryRun);
        $this->line("   Found {$oldCount} old messages");

        // Clean up deleted messages
        $this->info('🗑️  Cleaning up permanently deleted messages...');
        $deletedCount = $this->cleanupDeletedMessages($isDryRun);
        $this->line("   Found {$deletedCount} permanently deleted messages");

        // Clean up orphaned messages
        $this->info('🔗 Cleaning up orphaned messages...');
        $orphanedCount = $this->cleanupOrphanedMessages($isDryRun);
        $this->line("   Found {$orphanedCount} orphaned messages");

        $totalCleaned = $expiredCount + $oldCount + $deletedCount + $orphanedCount;

        $this->newLine();
        if ($isDryRun) {
            $this->info("✅ DRY RUN COMPLETE - Would clean up {$totalCleaned} messages");
            $this->line('   Run without --dry-run to perform actual cleanup');
        } else {
            $this->info("✅ CLEANUP COMPLETE - Cleaned up {$totalCleaned} messages");
        }

        return 0;
    }

    private function cleanupExpiredMessages(bool $isDryRun): int
    {
        $query = Message::where('expires_at', '<', now());

        if ($isDryRun) {
            return $query->count();
        }

        return $query->delete();
    }

    private function cleanupOldMessages(int $days, bool $isDryRun): int
    {
        $cutoffDate = now()->subDays($days);

        $query = Message::where('created_at', '<', $cutoffDate)
            ->where('message_type', '!=', Message::TYPE_SYSTEM)  // Keep system messages longer
            ->where('priority', '!=', Message::PRIORITY_URGENT);  // Keep urgent messages longer

        if ($isDryRun) {
            return $query->count();
        }

        return $query->delete();
    }

    private function cleanupDeletedMessages(bool $isDryRun): int
    {
        $query = Message::where('is_deleted_by_sender', true)
            ->where('is_deleted_by_recipient', true);

        if ($isDryRun) {
            return $query->count();
        }

        return $query->delete();
    }

    private function cleanupOrphanedMessages(bool $isDryRun): int
    {
        // Messages where both sender and recipient are deleted
        $query = Message::whereHas('sender', function ($q) {
            $q->whereNull('deleted_at');
        }, '=', 0)
            ->whereHas('recipient', function ($q) {
                $q->whereNull('deleted_at');
            }, '=', 0)
            ->where('message_type', '!=', Message::TYPE_SYSTEM);

        if ($isDryRun) {
            return $query->count();
        }

        return $query->delete();
    }
}
