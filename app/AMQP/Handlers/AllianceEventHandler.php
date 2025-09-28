<?php

namespace App\AMQP\Handlers;

use App\Models\Game\Alliance;
use Illuminate\Support\Facades\Log;

class AllianceEventHandler
{
    /**
     * Supported alliance event types keyed to handler callbacks.
     */
    private const SUPPORTED_EVENTS = [
        'alliance_created' => 'handleAllianceCreated',
        'alliance_member_joined' => 'handleAllianceMemberJoined',
        'alliance_member_left' => 'handleAllianceMemberLeft',
        'alliance_war_declared' => 'handleAllianceWarDeclared',
        'alliance_war_ended' => 'handleAllianceWarEnded',
        'alliance_diplomacy' => 'handleAllianceDiplomacy',
    ];

    /**
     * Handle an incoming alliance event message.
     */
    public function handle(array $message): bool
    {
        if (empty($message['alliance_id'])) {
            Log::warning('Alliance event rejected: missing alliance_id', ['message' => $message]);

            return false;
        }

        $eventType = $message['event_type'] ?? null;

        if ($eventType === null || ! isset(self::SUPPORTED_EVENTS[$eventType])) {
            Log::warning('Alliance event rejected: unsupported type', ['event_type' => $eventType]);

            return false;
        }

        $payload = $message['data'] ?? [];
        $handler = self::SUPPORTED_EVENTS[$eventType];

        $this->$handler((int) $message['alliance_id'], $payload);

        return true;
    }

    private function handleAllianceCreated(int $allianceId, array $payload): void
    {
        $alliance = Alliance::find($allianceId);

        Log::info('Alliance created event processed', [
            'alliance_id' => $allianceId,
            'exists' => $alliance !== null,
            'payload' => $payload,
        ]);
    }

    private function handleAllianceMemberJoined(int $allianceId, array $payload): void
    {
        Log::info('Alliance member joined event processed', [
            'alliance_id' => $allianceId,
            'payload' => $payload,
        ]);
    }

    private function handleAllianceMemberLeft(int $allianceId, array $payload): void
    {
        Log::info('Alliance member left event processed', [
            'alliance_id' => $allianceId,
            'payload' => $payload,
        ]);
    }

    private function handleAllianceWarDeclared(int $allianceId, array $payload): void
    {
        Log::info('Alliance war declared event processed', [
            'alliance_id' => $allianceId,
            'payload' => $payload,
        ]);
    }

    private function handleAllianceWarEnded(int $allianceId, array $payload): void
    {
        Log::info('Alliance war ended event processed', [
            'alliance_id' => $allianceId,
            'payload' => $payload,
        ]);
    }

    private function handleAllianceDiplomacy(int $allianceId, array $payload): void
    {
        Log::info('Alliance diplomacy event processed', [
            'alliance_id' => $allianceId,
            'payload' => $payload,
        ]);
    }
}
