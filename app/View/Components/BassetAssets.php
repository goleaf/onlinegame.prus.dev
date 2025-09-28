<?php

namespace App\View\Components;

use App\Helpers\BassetHelper;
use Illuminate\View\Component;

class BassetAssets extends Component
{
    public array $assets;

    public string $type;

    public bool $preconnect;

    /**
     * Create a new component instance.
     */
    public function __construct(array $assets, string $type = 'css', bool $preconnect = true)
    {
        $this->assets = $assets;
        $this->type = $type;
        $this->preconnect = $preconnect;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.basset-assets');
    }

    /**
     * Get the asset URLs
     */
    public function getAssetUrls(): array
    {
        $urls = [];

        foreach ($this->assets as $asset) {
            if (is_string($asset)) {
                $urls[] = basset($asset);
            } elseif (is_array($asset) && isset($asset['url'])) {
                $urls[] = basset($asset['url']);
            }
        }

        return $urls;
    }

    /**
     * Get preconnect tags
     */
    public function getPreconnectTags(): string
    {
        if (! $this->preconnect) {
            return '';
        }

        return BassetHelper::getPreconnectTags();
    }
}
