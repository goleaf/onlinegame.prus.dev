<?php

namespace App\Services;

use App\Models\User;
use App\Models\Game\Player;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsNotificationService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $fromNumber;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.sms.api_key', '');
        $this->apiSecret = config('services.sms.api_secret', '');
        $this->fromNumber = config('services.sms.from_number', '');
        $this->enabled = config('services.sms.enabled', false);
    }

    /**
     * Send SMS notification to user
     */
    public function sendSmsToUser(User $user, string $message, string $priority = 'normal'): bool
    {
        $startTime = microtime(true);
        
        ds('SmsNotificationService: Sending SMS to user', [
            'service' => 'SmsNotificationService',
            'method' => 'sendSmsToUser',
            'user_id' => $user->id,
            'message_length' => strlen($message),
            'priority' => $priority,
            'sms_enabled' => $this->enabled,
            'sms_time' => now()
        ]);
        
        if (!$this->canSendSms($user, $priority)) {
            ds('SmsNotificationService: SMS sending blocked', [
                'user_id' => $user->id,
                'reason' => 'Can send SMS check failed',
                'priority' => $priority
            ]);
            return false;
        }

        $phoneNumber = $this->getUserPhoneNumber($user);
        if (!$phoneNumber) {
            ds('SmsNotificationService: SMS skipped - no phone number', [
                'user_id' => $user->id,
                'priority' => $priority
            ]);
            
            Log::warning('SMS notification skipped - no phone number', [
                'user_id' => $user->id,
                'priority' => $priority
            ]);
            return false;
        }

        $smsResult = $this->sendSms($phoneNumber, $message, $priority);
        
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        
        ds('SmsNotificationService: SMS sending completed', [
            'user_id' => $user->id,
            'phone_number' => $phoneNumber,
            'success' => $smsResult,
            'total_time_ms' => $totalTime
        ]);

        return $smsResult;
    }

    /**
     * Send SMS notification to player
     */
    public function sendSmsToPlayer(Player $player, string $message, string $priority = 'normal'): bool
    {
        $user = $player->user;
        if (!$user) {
            Log::warning('SMS notification skipped - player has no user', [
                'player_id' => $player->id,
                'priority' => $priority
            ]);
            return false;
        }

        return $this->sendSmsToUser($user, $message, $priority);
    }

    /**
     * Send urgent battle notification via SMS
     */
    public function sendUrgentBattleNotification(Player $player, array $battleData): bool
    {
        $message = $this->formatBattleSms($battleData);
        return $this->sendSmsToPlayer($player, $message, 'urgent');
    }

    /**
     * Send village attack notification via SMS
     */
    public function sendVillageAttackNotification(Player $player, array $attackData): bool
    {
        $message = $this->formatVillageAttackSms($attackData);
        return $this->sendSmsToPlayer($player, $message, 'urgent');
    }

    /**
     * Send alliance message notification via SMS
     */
    public function sendAllianceMessageNotification(Player $player, array $messageData): bool
    {
        $message = $this->formatAllianceMessageSms($messageData);
        return $this->sendSmsToPlayer($player, $message, 'high');
    }

    /**
     * Send system announcement via SMS to all users with phones
     */
    public function sendSystemAnnouncement(string $message): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        $users = User::whereNotNull('phone')
                    ->whereNotNull('phone_e164')
                    ->where('sms_notifications_enabled', true)
                    ->get();

        foreach ($users as $user) {
            if ($this->sendSmsToUser($user, $message, 'high')) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        Log::info('System SMS announcement sent', $results);
        return $results;
    }

    /**
     * Send SMS to multiple users
     */
    public function sendBulkSms(array $userIds, string $message, string $priority = 'normal'): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        $users = User::whereIn('id', $userIds)
                    ->whereNotNull('phone')
                    ->whereNotNull('phone_e164')
                    ->get();

        foreach ($users as $user) {
            if ($this->sendSmsToUser($user, $message, $priority)) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Check if SMS can be sent to user
     */
    protected function canSendSms(User $user, string $priority): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$user->sms_notifications_enabled) {
            return false;
        }

        // Only send urgent SMS for high/urgent priority
        if ($priority === 'normal' && !$user->sms_urgent_only) {
            return true;
        }

        return in_array($priority, ['high', 'urgent']);
    }

    /**
     * Get user's phone number in E164 format
     */
    protected function getUserPhoneNumber(User $user): ?string
    {
        return $user->phone_e164 ?? $user->phone;
    }

    /**
     * Send SMS via API
     */
    protected function sendSms(string $phoneNumber, string $message, string $priority): bool
    {
        try {
            // For demo purposes, we'll log the SMS instead of actually sending
            // In production, integrate with SMS provider like Twilio, Nexmo, etc.
            
            Log::info('SMS sent', [
                'phone' => $phoneNumber,
                'message' => $message,
                'priority' => $priority,
                'length' => strlen($message)
            ]);

            // Example Twilio integration (uncomment when ready):
            /*
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post('https://api.twilio.com/2010-04-01/Accounts/' . $this->apiKey . '/Messages.json', [
                    'From' => $this->fromNumber,
                    'To' => $phoneNumber,
                    'Body' => $message
                ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'phone' => $phoneNumber,
                    'message_id' => $response->json('sid')
                ]);
                return true;
            } else {
                Log::error('SMS sending failed', [
                    'phone' => $phoneNumber,
                    'error' => $response->body()
                ]);
                return false;
            }
            */

            return true; // Demo mode - always return success
        } catch (\Exception $e) {
            Log::error('SMS sending exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Format battle SMS message
     */
    protected function formatBattleSms(array $battleData): string
    {
        $attacker = $battleData['attacker_name'] ?? 'Unknown';
        $village = $battleData['village_name'] ?? 'Unknown Village';
        $units = $battleData['units_attacking'] ?? [];
        
        $unitCount = array_sum($units);
        $message = "🚨 BATTLE ALERT: {$attacker} attacking {$village} with {$unitCount} units!";
        
        // Keep message under 160 characters
        if (strlen($message) > 160) {
            $message = "🚨 BATTLE: {$attacker} attacking {$village}!";
        }
        
        return $message;
    }

    /**
     * Format village attack SMS message
     */
    protected function formatVillageAttackSms(array $attackData): string
    {
        $attacker = $attackData['attacker_name'] ?? 'Unknown';
        $village = $attackData['village_name'] ?? 'Unknown Village';
        $time = $attackData['arrival_time'] ?? 'Unknown time';
        
        $message = "🏰 VILLAGE ATTACK: {$attacker} attacking {$village} - arrives {$time}";
        
        if (strlen($message) > 160) {
            $message = "🏰 ATTACK: {$attacker} attacking {$village} at {$time}";
        }
        
        return $message;
    }

    /**
     * Format alliance message SMS
     */
    protected function formatAllianceMessageSms(array $messageData): string
    {
        $sender = $messageData['sender_name'] ?? 'Alliance Member';
        $message = $messageData['message'] ?? 'New alliance message';
        
        // Truncate message to fit SMS
        if (strlen($message) > 100) {
            $message = substr($message, 0, 97) . '...';
        }
        
        return "🤝 ALLIANCE: {$sender}: {$message}";
    }

    /**
     * Get SMS statistics
     */
    public function getSmsStatistics(): array
    {
        return [
            'users_with_phone' => User::whereNotNull('phone')->count(),
            'users_with_e164' => User::whereNotNull('phone_e164')->count(),
            'sms_enabled_users' => User::where('sms_notifications_enabled', true)->count(),
            'urgent_only_users' => User::where('sms_urgent_only', true)->count(),
            'total_sms_capable' => User::whereNotNull('phone_e164')
                                        ->where('sms_notifications_enabled', true)
                                        ->count(),
        ];
    }
}
