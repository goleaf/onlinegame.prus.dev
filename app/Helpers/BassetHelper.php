<?php

namespace App\Helpers;

class BassetHelper
{
    /**
     * Common CDN assets that are frequently used
     */
    public static function getCommonAssets(): array
    {
        return [
            'bootstrap_css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            'bootstrap_js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            'font_awesome' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
            'tailwind_css' => 'https://cdn.jsdelivr.net/npm/tailwindcss@^2/dist/tailwind.min.css',
            'tailwind_play' => 'https://cdn.tailwindcss.com',
            'vue_js' => 'https://cdn.jsdelivr.net/npm/vue@2.6.10/dist/vue.min.js',
            'stripe_js' => 'https://js.stripe.com/v3',
            'fathom_js' => 'https://cdn.usefathom.com/script.js',
            'laravel_logo' => 'https://laravel.com/img/notification-logo.png',
        ];
    }

    /**
     * Get Basset URL for common assets
     */
    public static function asset(string $key): string
    {
        $assets = self::getCommonAssets();

        if (! isset($assets[$key])) {
            throw new \InvalidArgumentException("Asset key '{$key}' not found in common assets.");
        }

        return basset($assets[$key]);
    }

    /**
     * Get multiple assets as HTML tags
     */
    public static function getAssetsAsTags(array $keys, string $type = 'css'): string
    {
        $assets = self::getCommonAssets();
        $tags = '';

        foreach ($keys as $key) {
            if (! isset($assets[$key])) {
                continue;
            }

            $url = basset($assets[$key]);

            if ($type === 'css') {
                $tags .= "<link href=\"{$url}\" rel=\"stylesheet\">\n";
            } elseif ($type === 'js') {
                $tags .= "<script src=\"{$url}\"></script>\n";
            }
        }

        return $tags;
    }

    /**
     * Get Google Fonts with Basset
     */
    public static function googleFonts(string $family, array $weights = [400, 500, 600], string $display = 'swap'): string
    {
        $weightsStr = implode(',', $weights);
        $url = "https://fonts.bunny.net/css?family={$family}:{$weightsStr}&display={$display}";

        return basset($url);
    }

    /**
     * Get preconnect tags for performance
     */
    public static function getPreconnectTags(): string
    {
        return '<link rel="preconnect" href="https://fonts.bunny.net">'."\n"
            .'<link rel="preconnect" href="https://cdn.jsdelivr.net">'."\n"
            .'<link rel="preconnect" href="https://cdnjs.cloudflare.com">'."\n";
    }

    /**
     * Get critical CSS assets for above-the-fold content
     */
    public static function getCriticalAssets(): array
    {
        return [
            'bootstrap_css',
            'font_awesome',
        ];
    }

    /**
     * Get non-critical assets for lazy loading
     */
    public static function getNonCriticalAssets(): array
    {
        return [
            'bootstrap_js',
            'vue_js',
            'stripe_js',
            'fathom_js',
        ];
    }
}
