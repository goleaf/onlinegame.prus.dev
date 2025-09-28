<?php

namespace App\Services;

class FathomAnalytics
{
    protected string $siteId;

    public function __construct()
    {
        $this->siteId = config('services.fathom.site_id', '');
    }

    /**
     * Get the Fathom site ID
     */
    public function getSiteId(): string
    {
        return $this->siteId;
    }

    /**
     * Check if Fathom is configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->siteId);
    }

    /**
     * Generate Fathom tracking script
     */
    public function getTrackingScript(): string
    {
        if (! $this->isConfigured()) {
            return '';
        }

        return sprintf(
            '<script src="%s" data-site="%s" defer></script>',
            basset('https://cdn.usefathom.com/script.js'),
            $this->siteId
        );
    }

    /**
     * Generate Alpine.js Fathom helper script
     */
    public function getAlpineHelperScript(): string
    {
        return '
        <script>
        document.addEventListener("alpine:init", () => {
            Alpine.magic("fathom", () => {
                return {
                    track(eventName, value = null) {
                        if (typeof fathom !== "undefined") {
                            if (value !== null) {
                                fathom.trackGoal(eventName, value);
                            } else {
                                fathom.trackGoal(eventName);
                            }
                        }
                    },
                    conversion(eventName, value) {
                        if (typeof fathom !== "undefined") {
                            fathom.trackGoal(eventName, value);
                        }
                    }
                };
            });
        });

        // Alpine directive for click tracking
        Alpine.directive("track-click", (el, { expression }, { evaluate }) => {
            el.addEventListener("click", () => {
                if (typeof fathom !== "undefined") {
                    const config = evaluate(expression);
                    if (typeof config === "object" && config !== null) {
                        if (config.value !== undefined) {
                            fathom.trackGoal(config.event, config.value);
                        } else {
                            fathom.trackGoal(config.event);
                        }
                    } else {
                        fathom.trackGoal(config);
                    }
                }
            });
        });

        // Alpine directive for form submission tracking
        Alpine.directive("track-submit", (el, { expression }, { evaluate }) => {
            el.addEventListener("submit", () => {
                if (typeof fathom !== "undefined") {
                    const eventName = evaluate(expression);
                    fathom.trackGoal(eventName);
                }
            });
        });
        </script>';
    }

    /**
     * Get complete Fathom setup including tracking and Alpine helpers
     */
    public function getCompleteSetup(): string
    {
        return $this->getTrackingScript().$this->getAlpineHelperScript();
    }
}
