<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get Authorization header
        $authHeader = $request->header('Authorization');

        // Check format "Bearer <token>"
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return response()->json(['error' => 'Unauthorized - No Bearer Token'], 401);
        }

        $token = $matches[1];

        // ✅ Replace this with your own logic (e.g., check DB, config, env, etc.)
        $validToken = env('API_BEARER_TOKEN', 'my-secret-dummy-token');

        if ($token !== $validToken) {
            return response()->json(['error' => 'Unauthorized - Invalid Token'], 401);
        }

        // Token is valid → allow request
        return $next($request);
    }
}
