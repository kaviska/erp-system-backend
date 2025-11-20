<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;
use App\Helper\Response as ApiResponse;

class TokenValidator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Get token from request (multiple ways)
            $token = $this->getTokenFromRequest($request);
            
            if (!$token) {
                return ApiResponse::error('', 'Token is required', 401);
            }

            // Validate the token
            $personalAccessToken = PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return ApiResponse::error('', 'Invalid token', 401);
            }

            // Check if token is expired
            if ($this->isTokenExpired($personalAccessToken)) {
                return ApiResponse::error('', 'Token has expired', 401);
            }

            // Check if user is active
            if (!$personalAccessToken->tokenable || !$personalAccessToken->tokenable->id) {
                return ApiResponse::error('', 'User not found', 401);
            }

            // Set authenticated user for the request
            $request->setUserResolver(function () use ($personalAccessToken) {
                return $personalAccessToken->tokenable;
            });

            return $next($request);
            
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 'Authentication failed', 401);
        }
    }

    /**
     * Get token from request in multiple ways
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        // 1. Check Authorization header (Bearer token)
        if ($bearerToken = $request->bearerToken()) {
            return $bearerToken;
        }

        // 2. Check Authorization header manually
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // 3. Check query parameter (not recommended for production)
        if ($request->has('token')) {
            return $request->get('token');
        }

        // 4. Check custom header
        if ($request->header('X-API-Token')) {
            return $request->header('X-API-Token');
        }

        return null;
    }

    /**
     * Check if token is expired
     */
    private function isTokenExpired(PersonalAccessToken $token): bool
    {
        // Check if token has expiration date set
        if ($token->expires_at) {
            return $token->expires_at->isPast();
        }

        // Check global expiration setting from config
        $expiration = config('sanctum.expiration');
        if ($expiration) {
            return $token->created_at->addMinutes($expiration)->isPast();
        }

        return false;
    }
}
