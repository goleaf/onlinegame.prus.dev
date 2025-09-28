<?php

namespace App\Services;

use App\Models\Game\Achievement;
use App\Models\Game\Player;
use App\Models\Game\PlayerAchievement;
use App\Utilities\LoggingUtil;
use Illuminate\Support\Facades\Http;
use LaraUtilX\Utilities\CachingUtil;

class SocialMediaService
{
    protected CachingUtil $cachingUtil;

    protected LoggingUtil $loggingUtil;

    public function __construct()
    {
        $this->cachingUtil = new CachingUtil(3600, ['social_media']);
        $this->loggingUtil = new LoggingUtil();
    }

    /**
     * Share achievement on social media
     */
    public function shareAchievement(PlayerAchievement $playerAchievement, array $platforms = ['twitter', 'facebook']): array
    {
        $player = $playerAchievement->player;
        $achievement = $playerAchievement->achievement;

        $shareData = [
            'text' => "🎉 I just unlocked the achievement '{$achievement->name}' in the game! {$achievement->description}",
            'url' => config('app.url').'/achievements/'.$achievement->id,
            'image' => $this->generateAchievementImage($achievement, $player),
            'hashtags' => ['#GameAchievement', '#Gaming', '#OnlineGame'],
        ];

        $results = [];

        foreach ($platforms as $platform) {
            try {
                $result = $this->shareToPlatform($platform, $shareData, $player);
                $results[$platform] = $result;

                $this->loggingUtil->info('Achievement shared on social media', [
                    'player_id' => $player->id,
                    'achievement_id' => $achievement->id,
                    'platform' => $platform,
                    'success' => $result['success'],
                ]);
            } catch (\Exception $e) {
                $results[$platform] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];

                $this->loggingUtil->error('Failed to share achievement on social media', [
                    'player_id' => $player->id,
                    'achievement_id' => $achievement->id,
                    'platform' => $platform,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Share battle victory on social media
     */
    public function shareBattleVictory(Player $player, array $battleData, array $platforms = ['twitter', 'facebook']): array
    {
        $shareData = [
            'text' => "⚔️ Victory! I won a battle against {$battleData['enemy_name']} and captured {$battleData['resources_captured']} resources!",
            'url' => config('app.url').'/battles/'.$battleData['battle_id'],
            'image' => $this->generateBattleImage($battleData),
            'hashtags' => ['#BattleVictory', '#Gaming', '#OnlineGame', '#Strategy'],
        ];

        $results = [];

        foreach ($platforms as $platform) {
            try {
                $result = $this->shareToPlatform($platform, $shareData, $player);
                $results[$platform] = $result;
            } catch (\Exception $e) {
                $results[$platform] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Share village milestone on social media
     */
    public function shareVillageMilestone(Player $player, array $villageData, array $platforms = ['twitter', 'facebook']): array
    {
        $shareData = [
            'text' => "🏘️ My village '{$villageData['village_name']}' has reached {$villageData['milestone']}! Population: {$villageData['population']}",
            'url' => config('app.url').'/villages/'.$villageData['village_id'],
            'image' => $this->generateVillageImage($villageData),
            'hashtags' => ['#VillageGrowth', '#Gaming', '#OnlineGame', '#Strategy'],
        ];

        $results = [];

        foreach ($platforms as $platform) {
            try {
                $result = $this->shareToPlatform($platform, $shareData, $player);
                $results[$platform] = $result;
            } catch (\Exception $e) {
                $results[$platform] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Share to specific platform
     */
    protected function shareToPlatform(string $platform, array $shareData, Player $player): array
    {
        switch ($platform) {
            case 'twitter':
                return $this->shareToTwitter($shareData, $player);
            case 'facebook':
                return $this->shareToFacebook($shareData, $player);
            case 'discord':
                return $this->shareToDiscord($shareData, $player);
            case 'telegram':
                return $this->shareToTelegram($shareData, $player);
            default:
                throw new \Exception("Unsupported platform: {$platform}");
        }
    }

    /**
     * Share to Twitter
     */
    protected function shareToTwitter(array $shareData, Player $player): array
    {
        $twitterConfig = config('social.twitter');

        if (! $twitterConfig || ! $twitterConfig['enabled']) {
            return ['success' => false, 'error' => 'Twitter integration not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$twitterConfig['bearer_token'],
                'Content-Type' => 'application/json',
            ])->post('https://api.twitter.com/2/tweets', [
                'text' => $this->formatTweetText($shareData),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'tweet_id' => $response->json('data.id'),
                    'url' => 'https://twitter.com/user/status/'.$response->json('data.id'),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json('detail', 'Unknown error'),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Share to Facebook
     */
    protected function shareToFacebook(array $shareData, Player $player): array
    {
        $facebookConfig = config('social.facebook');

        if (! $facebookConfig || ! $facebookConfig['enabled']) {
            return ['success' => false, 'error' => 'Facebook integration not configured'];
        }

        try {
            $response = Http::post("https://graph.facebook.com/v18.0/{$facebookConfig['page_id']}/feed", [
                'message' => $shareData['text'],
                'link' => $shareData['url'],
                'access_token' => $facebookConfig['access_token'],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'post_id' => $response->json('id'),
                    'url' => $shareData['url'],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json('error.message', 'Unknown error'),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Share to Discord
     */
    protected function shareToDiscord(array $shareData, Player $player): array
    {
        $discordConfig = config('social.discord');

        if (! $discordConfig || ! $discordConfig['enabled']) {
            return ['success' => false, 'error' => 'Discord integration not configured'];
        }

        try {
            $response = Http::post($discordConfig['webhook_url'], [
                'content' => $shareData['text'],
                'embeds' => [
                    [
                        'title' => 'Game Achievement',
                        'description' => $shareData['text'],
                        'url' => $shareData['url'],
                        'color' => 0x00FF00,
                        'footer' => [
                            'text' => 'Shared from Online Game',
                        ],
                        'timestamp' => now()->toISOString(),
                    ],
                ],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('id'),
                    'url' => $shareData['url'],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to send Discord message',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Share to Telegram
     */
    protected function shareToTelegram(array $shareData, Player $player): array
    {
        $telegramConfig = config('social.telegram');

        if (! $telegramConfig || ! $telegramConfig['enabled']) {
            return ['success' => false, 'error' => 'Telegram integration not configured'];
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$telegramConfig['bot_token']}/sendMessage", [
                'chat_id' => $telegramConfig['channel_id'],
                'text' => $shareData['text'],
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('result.message_id'),
                    'url' => $shareData['url'],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response->json('description', 'Unknown error'),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format tweet text
     */
    protected function formatTweetText(array $shareData): string
    {
        $text = $shareData['text'];
        $url = $shareData['url'];
        $hashtags = implode(' ', $shareData['hashtags']);

        // Twitter character limit is 280, so we need to be careful
        $maxLength = 280;
        $urlLength = strlen($url) + 1; // +1 for space
        $hashtagLength = strlen($hashtags) + 1; // +1 for space
        $availableLength = $maxLength - $urlLength - $hashtagLength;

        if (strlen($text) > $availableLength) {
            $text = substr($text, 0, $availableLength - 3).'...';
        }

        return $text.' '.$url.' '.$hashtags;
    }

    /**
     * Generate achievement image
     */
    protected function generateAchievementImage(Achievement $achievement, Player $player): string
    {
        // This would typically generate an image using GD or similar
        // For now, we'll return a placeholder
        return config('app.url').'/images/achievements/'.$achievement->id.'.png';
    }

    /**
     * Generate battle image
     */
    protected function generateBattleImage(array $battleData): string
    {
        // This would typically generate a battle result image
        return config('app.url').'/images/battles/'.$battleData['battle_id'].'.png';
    }

    /**
     * Generate village image
     */
    protected function generateVillageImage(array $villageData): string
    {
        // This would typically generate a village screenshot
        return config('app.url').'/images/villages/'.$villageData['village_id'].'.png';
    }

    /**
     * Get social media statistics
     */
    public function getSocialMediaStatistics(): array
    {
        $cacheKey = 'social_media_statistics';

        return $this->cachingUtil->remember($cacheKey, 1800, function () {
            // This would typically query a social_media_shares table
            // For now, we'll return mock data
            return [
                'total_shares' => 0,
                'platform_breakdown' => [
                    'twitter' => 0,
                    'facebook' => 0,
                    'discord' => 0,
                    'telegram' => 0,
                ],
                'content_breakdown' => [
                    'achievements' => 0,
                    'battles' => 0,
                    'villages' => 0,
                    'other' => 0,
                ],
                'recent_shares' => [],
            ];
        });
    }

    /**
     * Get available platforms
     */
    public function getAvailablePlatforms(): array
    {
        return [
            'twitter' => [
                'name' => 'Twitter',
                'enabled' => config('social.twitter.enabled', false),
                'icon' => 'fab fa-twitter',
            ],
            'facebook' => [
                'name' => 'Facebook',
                'enabled' => config('social.facebook.enabled', false),
                'icon' => 'fab fa-facebook',
            ],
            'discord' => [
                'name' => 'Discord',
                'enabled' => config('social.discord.enabled', false),
                'icon' => 'fab fa-discord',
            ],
            'telegram' => [
                'name' => 'Telegram',
                'enabled' => config('social.telegram.enabled', false),
                'icon' => 'fab fa-telegram',
            ],
        ];
    }

    /**
     * Get share templates
     */
    public function getShareTemplates(): array
    {
        return [
            'achievement' => [
                'text' => "🎉 I just unlocked the achievement '{achievement_name}' in the game! {achievement_description}",
                'hashtags' => ['#GameAchievement', '#Gaming', '#OnlineGame'],
            ],
            'battle_victory' => [
                'text' => '⚔️ Victory! I won a battle against {enemy_name} and captured {resources_captured} resources!',
                'hashtags' => ['#BattleVictory', '#Gaming', '#OnlineGame', '#Strategy'],
            ],
            'village_milestone' => [
                'text' => "🏘️ My village '{village_name}' has reached {milestone}! Population: {population}",
                'hashtags' => ['#VillageGrowth', '#Gaming', '#OnlineGame', '#Strategy'],
            ],
            'alliance_war' => [
                'text' => "🤝 My alliance '{alliance_name}' has declared war on {enemy_alliance}! Join the battle!",
                'hashtags' => ['#AllianceWar', '#Gaming', '#OnlineGame', '#Strategy'],
            ],
        ];
    }
}
