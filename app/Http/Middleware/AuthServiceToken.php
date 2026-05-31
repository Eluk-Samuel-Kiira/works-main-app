<?php
// app/Http/Middleware/AuthServiceToken.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthServiceToken
{
    public function handle(Request $request, Closure $next)
    {
        $incoming = $request->header('X-App-Key');
        $expected = config('services.web_app.service_token');

        if (empty($expected) || $incoming !== $expected) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}