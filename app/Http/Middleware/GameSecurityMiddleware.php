<?php

namespace App\Http\Middleware;

use App\Services\GameErrorHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Closure;

class GameSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = null)
    {
        $startTime = microtime(true);

        ds('GameSecurityMiddleware: Security check started', [
            'middleware' => 'GameSecurityMiddleware',
            'action' => $action,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'security_check_time' => now()
        ]);

        try {
            // Rate limiting based on action
            if ($action) {
                $rateLimitStart = microtime(true);
                $this->applyRateLimit($request, $action);
                $rateLimitTime = round((microtime(true) - $rateLimitStart) * 1000, 2);
                ds('GameSecurityMiddleware: Rate limit check completed', [
                    'action' => $action,
                    'rate_limit_time_ms' => $rateLimitTime
                ]);
            }

            // Validate request integrity
            $integrityStart = microtime(true);
            $this->validateRequestIntegrity($request);
            $integrityTime = round((microtime(true) - $integrityStart) * 1000, 2);
            ds('GameSecurityMiddleware: Request integrity validated', [
                'integrity_check_time_ms' => $integrityTime
            ]);

            // Check for suspicious patterns
            $suspiciousStart = microtime(true);
            $this->checkSuspiciousActivity($request);
            $suspiciousTime = round((microtime(true) - $suspiciousStart) * 1000, 2);
            ds('GameSecurityMiddleware: Suspicious activity check completed', [
                'suspicious_check_time_ms' => $suspiciousTime
            ]);

            // Log security event
            $this->logSecurityEvent($request, $action);

            // Apply additional security headers
            $response = $next($request);
            $this->addSecurityHeaders($response);

            // Log performance
            $duration = microtime(true) - $startTime;
            $totalTime = round($duration * 1000, 2);
            
            ds('GameSecurityMiddleware: Security check completed successfully', [
                'action' => $action,
                'total_time_ms' => $totalTime,
                'status_code' => $response->getStatusCode(),
                'memory_usage' => memory_get_usage(true)
            ]);
            
            if ($duration > 1.0) {
                Log::channel('security')->warning('Slow request detected', [
                    'action' => $action,
                    'duration' => $duration,
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                ]);
            }

            return $response;
        } catch (\Exception $e) {
            ds('GameSecurityMiddleware: Security check failed', [
                'action' => $action,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            
            GameErrorHandler::handleGameError($e, [
                'action' => 'security_middleware',
                'request_action' => $action,
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);

            throw $e;
        }
    }

    /**
     * Apply rate limiting based on action type
     */
    private function applyRateLimit(Request $request, string $action): void
    {
        $rateLimits = config('game.security.rate_limiting', []);
        $limit = $rateLimits[$action] ?? 60;  // Default: 60 requests per minute

        $key = 'game_action:' . $action . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);

            Log::channel('security')->warning('Rate limit exceeded', [
                'action' => $action,
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'retry_after' => $seconds,
            ]);

            abort(429, "Too many requests. Try again in {$seconds} seconds.");
        }

        RateLimiter::hit($key, 60);  // 1 minute decay
    }

    /**
     * Validate request integrity
     */
    private function validateRequestIntegrity(Request $request): void
    {
        // Check for SQL injection patterns
        $suspiciousPatterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\balter\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(\bscript\b.*\b>/i',
            '/(<.*\bscript\b)/i',
        ];

        $requestData = json_encode($request->all());

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $requestData)) {
                Log::channel('security')->critical('SQL injection attempt detected', [
                    'ip' => $request->ip(),
                    'user_id' => auth()->id(),
                    'url' => $request->fullUrl(),
                    'data' => $requestData,
                    'pattern' => $pattern,
                ]);

                abort(403, 'Suspicious request detected.');
            }
        }
    }

    /**
     * Check for suspicious activity patterns
     */
    private function checkSuspiciousActivity(Request $request): void
    {
        $userId = auth()->id();
        $ip = $request->ip();

        // Check for rapid successive requests
        $key = 'rapid_requests:' . $ip . ':' . $userId;
        $recentRequests = RateLimiter::attempts($key);

        if ($recentRequests > 10) {
            Log::channel('security')->warning('Rapid requests detected', [
                'ip' => $ip,
                'user_id' => $userId,
                'request_count' => $recentRequests,
                'url' => $request->fullUrl(),
            ]);
        }

        RateLimiter::hit($key, 60);

        // Check for unusual user agent
        $userAgent = $request->userAgent();
        if (empty($userAgent) || strlen($userAgent) < 10) {
            Log::channel('security')->warning('Suspicious user agent', [
                'ip' => $ip,
                'user_id' => $userId,
                'user_agent' => $userAgent,
            ]);
        }

        // Check for requests from suspicious IPs (basic check)
        if ($this->isSuspiciousIP($ip)) {
            Log::channel('security')->critical('Request from suspicious IP', [
                'ip' => $ip,
                'user_id' => $userId,
                'url' => $request->fullUrl(),
            ]);

            abort(403, 'Access denied.');
        }
    }

    /**
     * Check if IP is suspicious
     */
    private function isSuspiciousIP(string $ip): bool
    {
        // Basic checks for suspicious IPs
        $suspiciousPatterns = [
            '/^10\./',  // Private networks (in production, this might be legitimate)
            '/^192\.168\./',  // Private networks
            '/^172\.(1[6-9]|2[0-9]|3[01])\./',  // Private networks
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $ip)) {
                // In a real application, you might want to allow these in development
                // but block them in production, or maintain a whitelist
                return false;  // For now, we'll allow private IPs
            }
        }

        // You could add more sophisticated checks here:
        // - Check against known malicious IP databases
        // - Check for VPN/Proxy usage
        // - Geographic location checks

        return false;
    }

    /**
     * Log security event
     */
    private function logSecurityEvent(Request $request, string $action = null): void
    {
        Log::channel('security')->info('Game security event', [
            'action' => $action,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'headers' => $this->getSecurityHeaders($request),
        ]);
    }

    /**
     * Get security-related headers
     */
    private function getSecurityHeaders(Request $request): array
    {
        return [
            'x-forwarded-for' => $request->header('X-Forwarded-For'),
            'x-real-ip' => $request->header('X-Real-IP'),
            'x-forwarded-proto' => $request->header('X-Forwarded-Proto'),
            'referer' => $request->header('Referer'),
            'origin' => $request->header('Origin'),
        ];
    }

    /**
     * Add security headers to response
     */
    private function addSecurityHeaders($response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
    }

    /**
     * Generate security report
     */
    public static function generateSecurityReport(): array
    {
        try {
            $report = [
                'timestamp' => now()->toISOString(),
                'rate_limits' => [],
                'suspicious_activity' => [],
                'security_events' => [],
            ];

            // Get rate limit statistics
            $rateLimitKeys = \Illuminate\Support\Facades\Cache::get('rate_limit_keys', []);
            foreach ($rateLimitKeys as $key => $attempts) {
                $report['rate_limits'][$key] = $attempts;
            }

            // Get recent security events from logs
            // This would typically query your log storage
            $report['security_events'] = [
                'total_events' => 'Query from log storage',
                'critical_events' => 'Query from log storage',
                'blocked_requests' => 'Query from log storage',
            ];

            return $report;
        } catch (\Exception $e) {
            Log::error('Failed to generate security report', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
