<?php

namespace App\Http\Middleware;

use App\Services\GameSeoService;
use App\Services\SeoAnalyticsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Closure;
use LaraUtilX\Utilities\LoggingUtil;

class SeoMiddleware
{
    protected GameSeoService $seoService;

    public function __construct(GameSeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply SEO middleware to GET requests that return HTML
        if ($request->isMethod('GET') && $request->wantsHtml()) {
            $this->applySeoMetadata($request);
        }

        return $response;
    }

    /**
     * Apply SEO metadata based on the current route
     */
    protected function applySeoMetadata(Request $request): void
    {
        $routeName = $request->route()?->getName();
        $path = $request->path();

        // Apply SEO metadata based on route patterns
        switch (true) {
            case $path === '/':
                $this->seoService->setGameIndexSeo();
                $this->seoService->setGameStructuredData();
                break;

            case str_starts_with($path, 'game/dashboard'):
                if (auth()->check() && auth()->user()->player) {
                    $this->seoService->setDashboardSeo(auth()->user()->player);
                }
                break;

            case str_starts_with($path, 'game/village/'):
                if (auth()->check() && auth()->user()->player) {
                    $villageId = $request->route('village');
                    if ($villageId) {
                        $village = \App\Models\Game\Village::find($villageId);
                        if ($village && $village->player_id === auth()->user()->player->id) {
                            $this->seoService->setVillageSeo($village, auth()->user()->player);
                        }
                    }
                }
                break;

            case str_starts_with($path, 'game/map'):
                $world = \App\Models\Game\World::first();
                $this->seoService->setWorldMapSeo($world);
                break;

            case str_starts_with($path, 'game/features'):
            case str_starts_with($path, 'game/about'):
                $this->seoService->setGameFeaturesSeo();
                break;

            default:
                // Apply default SEO for other game routes
                if (str_starts_with($path, 'game/')) {
                    $this->seoService->setGameIndexSeo();
                }
                break;
        }
    }
}
