<?php

namespace App\Console\Commands;

use App\Models\Game\Message;
use App\Models\Game\Notification;
use App\Models\Game\Player;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:manage 
                            {action : Action to perform (send|read|archive|delete|expire|stats)}
                            {--sender-id= : Sender player ID}
                            {--recipient-id= : Recipient player ID}
                            {--message-type= : Message type (private|system|alliance|public|announcement)}
                            {--priority=normal : Message priority (low|normal|high|urgent)}
                            {--subject= : Message subject}
                            {--content= : Message content}
                            {--duration=24 : Duration in hours}
                            {--force : Force the operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage messaging system - send, read, archive messages and handle notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        $this->info('📧 Messaging System Management');
        $this->info('=============================');

        switch ($action) {
            case 'send':
                $this->sendMessage();
                break;
            case 'read':
                $this->readMessage();
                break;
            case 'archive':
                $this->archiveMessage();
                break;
            case 'delete':
                $this->deleteMessage();
                break;
            case 'expire':
                $this->expireMessages();
                break;
            case 'stats':
                $this->showMessageStats();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    /**
     * Send a new message.
     */
    protected function sendMessage(): void
    {
        $this->info('📤 Sending message...');

        $senderId = $this->option('sender-id');
        $recipientId = $this->option('recipient-id');
        $type = $this->option('message-type') ?? 'private';
        $priority = $this->option('priority');
        $subject = $this->option('subject');
        $content = $this->option('content');
        $duration = (int) $this->option('duration');

        if (!$recipientId) {
            $this->error('Recipient ID is required');
            return;
        }

        if (!$subject) {
            $subject = $this->ask('Enter message subject');
        }

        if (!$content) {
            $content = $this->ask('Enter message content');
        }

        $recipient = Player::find($recipientId);
        $sender = $senderId ? Player::find($senderId) : null;

        if (!$recipient) {
            $this->error('Recipient not found');
            return;
        }

        $message = Message::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'content' => $content,
            'type' => $type,
            'priority' => $priority,
            'status' => 'unread',
            'expires_at' => $duration > 0 ? now()->addHours($duration) : null,
            'is_important' => $priority === 'urgent' || $type === 'announcement',
        ]);

        // Create notification for the recipient
        $this->createNotification($recipient, $message);

        $this->info("✅ Message sent successfully");
        $this->line("  → To: {$recipient->name}");
        $this->line("  → Subject: {$message->subject}");
        $this->line("  → Type: {$message->type_display_name}");
        $this->line("  → Priority: {$message->priority_display_name}");
    }

    /**
     * Create notification for message.
     */
    protected function createNotification(Player $player, Message $message): void
    {
        $notificationType = match($message->type) {
            'system' => 'info',
            'alliance' => 'diplomacy',
            'public' => 'info',
            'announcement' => 'info',
            default => 'info'
        };

        $icon = match($message->type) {
            'private' => 'envelope',
            'system' => 'cog',
            'alliance' => 'users',
            'public' => 'globe',
            'announcement' => 'bullhorn',
            default => 'envelope'
        };

        Notification::create([
            'player_id' => $player->id,
            'title' => "New {$message->type_display_name}",
            'message' => "You have received a new message: {$message->subject}",
            'type' => $notificationType,
            'priority' => $message->priority,
            'status' => 'unread',
            'icon' => $icon,
            'action_url' => "/messages/{$message->id}",
            'expires_at' => $message->expires_at,
            'is_persistent' => $message->is_important,
            'is_auto_dismiss' => !$message->is_important,
            'auto_dismiss_seconds' => $message->is_important ? 30 : 5,
        ]);
    }

    /**
     * Read a message.
     */
    protected function readMessage(): void
    {
        $this->info('📖 Reading message...');

        $messageId = $this->ask('Enter message ID to read');
        
        if (!$messageId) {
            $this->error('Message ID is required');
            return;
        }

        $message = Message::with(['sender', 'recipient'])->find($messageId);
        
        if (!$message) {
            $this->error('Message not found');
            return;
        }

        // Mark as read
        $message->markAsRead();

        $this->info("📧 Message Details");
        $this->line("==================");
        $this->line("Subject: {$message->subject}");
        $this->line("From: " . ($message->sender ? $message->sender->name : 'System'));
        $this->line("To: {$message->recipient->name}");
        $this->line("Type: {$message->type_display_name}");
        $this->line("Priority: {$message->priority_display_name}");
        $this->line("Status: {$message->status_display_name}");
        $this->line("Sent: {$message->created_at->format('Y-m-d H:i:s')}");
        
        if ($message->expires_at) {
            $this->line("Expires: {$message->expires_at->format('Y-m-d H:i:s')}");
        }
        
        $this->line("");
        $this->line("Content:");
        $this->line($message->content);
    }

    /**
     * Archive a message.
     */
    protected function archiveMessage(): void
    {
        $this->info('📁 Archiving message...');

        $messageId = $this->ask('Enter message ID to archive');
        
        if (!$messageId) {
            $this->error('Message ID is required');
            return;
        }

        $message = Message::find($messageId);
        
        if (!$message) {
            $this->error('Message not found');
            return;
        }

        if ($message->archive()) {
            $this->info("✅ Message '{$message->subject}' archived successfully");
        } else {
            $this->error('Failed to archive message');
        }
    }

    /**
     * Delete a message.
     */
    protected function deleteMessage(): void
    {
        $this->info('🗑️ Deleting message...');

        $messageId = $this->ask('Enter message ID to delete');
        
        if (!$messageId) {
            $this->error('Message ID is required');
            return;
        }

        $message = Message::find($messageId);
        
        if (!$message) {
            $this->error('Message not found');
            return;
        }

        if (!$this->option('force') && !$this->confirm('Are you sure you want to delete this message?')) {
            $this->info('Message deletion cancelled');
            return;
        }

        if ($message->delete()) {
            $this->info("✅ Message '{$message->subject}' deleted successfully");
        } else {
            $this->error('Failed to delete message');
        }
    }

    /**
     * Expire old messages.
     */
    protected function expireMessages(): void
    {
        $this->info('⏰ Checking for expired messages...');

        $expiredCount = Message::where('status', '!=', 'deleted')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'deleted']);

        // Also expire notifications
        $expiredNotifications = Notification::where('status', '!=', 'dismissed')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'dismissed']);

        $this->info("✅ Expired {$expiredCount} messages and {$expiredNotifications} notifications");
    }

    /**
     * Show messaging statistics.
     */
    protected function showMessageStats(): void
    {
        $this->info('📊 Messaging Statistics');
        $this->info('========================');

        $stats = [
            'Total Messages' => Message::count(),
            'Unread Messages' => Message::where('status', 'unread')->count(),
            'Read Messages' => Message::where('status', 'read')->count(),
            'Archived Messages' => Message::where('status', 'archived')->count(),
            'Deleted Messages' => Message::where('status', 'deleted')->count(),
            'Total Notifications' => Notification::count(),
            'Unread Notifications' => Notification::where('status', 'unread')->count(),
        ];

        foreach ($stats as $label => $count) {
            $this->line("  {$label}: {$count}");
        }

        // Show message types breakdown
        $this->info('');
        $this->info('Message Types:');
        $typeStats = Message::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        foreach ($typeStats as $stat) {
            $this->line("  {$stat->type}: {$stat->count}");
        }

        // Show notification types breakdown
        $this->info('');
        $this->info('Notification Types:');
        $notificationStats = Notification::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        foreach ($notificationStats as $stat) {
            $this->line("  {$stat->type}: {$stat->count}");
        }

        // Show recent messages
        $this->info('');
        $this->info('Recent Messages:');
        $recentMessages = Message::with(['sender', 'recipient'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentMessages as $message) {
            $sender = $message->sender ? $message->sender->name : 'System';
            $this->line("  → {$message->subject} ({$message->type_display_name}) - {$message->status_display_name}");
            $this->line("    From: {$sender} → {$message->recipient->name}");
        }
    }

    /**
     * Send system announcement to all players.
     */
    protected function sendSystemAnnouncement(): void
    {
        $this->info('📢 Sending system announcement...');

        $subject = $this->ask('Enter announcement subject');
        $content = $this->ask('Enter announcement content');
        $priority = $this->ask('Enter priority (low|normal|high|urgent)', 'normal');
        $duration = (int) $this->ask('Enter duration in hours (0 for permanent)', 24);

        $players = Player::all();
        $sentCount = 0;

        foreach ($players as $player) {
            $message = Message::create([
                'sender_id' => null, // System message
                'recipient_id' => $player->id,
                'subject' => $subject,
                'content' => $content,
                'type' => 'announcement',
                'priority' => $priority,
                'status' => 'unread',
                'expires_at' => $duration > 0 ? now()->addHours($duration) : null,
                'is_important' => $priority === 'urgent',
            ]);

            $this->createNotification($player, $message);
            $sentCount++;
        }

        $this->info("✅ System announcement sent to {$sentCount} players");
    }

    /**
     * Send alliance message to all alliance members.
     */
    protected function sendAllianceMessage(): void
    {
        $this->info('👥 Sending alliance message...');

        $allianceId = $this->ask('Enter alliance ID');
        $subject = $this->ask('Enter message subject');
        $content = $this->ask('Enter message content');
        $priority = $this->ask('Enter priority (low|normal|high|urgent)', 'normal');

        // Get alliance members (assuming alliance_id column exists in players table)
        $players = Player::where('alliance_id', $allianceId)->get();
        
        if ($players->isEmpty()) {
            $this->error('No players found in alliance');
            return;
        }

        $sentCount = 0;

        foreach ($players as $player) {
            $message = Message::create([
                'sender_id' => null, // Alliance message
                'recipient_id' => $player->id,
                'subject' => $subject,
                'content' => $content,
                'type' => 'alliance',
                'priority' => $priority,
                'status' => 'unread',
                'is_important' => $priority === 'urgent',
            ]);

            $this->createNotification($player, $message);
            $sentCount++;
        }

        $this->info("✅ Alliance message sent to {$sentCount} players");
    }
}