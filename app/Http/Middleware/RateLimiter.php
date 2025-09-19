<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as LaravelRateLimiter;
use Symfony\Component\HttpFoundation\Response;
use App\Helper\Response as ResponseHelper;

class RateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $limit = 60): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        if (LaravelRateLimiter::tooManyAttempts($key, $limit)) {
            return ResponseHelper::error([
                'retry_after' => LaravelRateLimiter::availableIn($key)
            ], 'Too many requests. Please try again later.', 429);
        }
        
        LaravelRateLimiter::hit($key, 60); // 60 seconds window
        
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $limit - LaravelRateLimiter::attempts($key)),
        ]);
        
        return $response;
    }
    
    /**
     * Resolve the rate limiting signature for the request.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        return sha1($request->ip() . '|' . $request->getPathInfo());
    }
}
