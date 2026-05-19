<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsAccess
{
    public function handle(Request $request, Closure $next, string $level = 'admin'): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        match ($level) {
            'admin'    => abort_unless(
                $user->hasAnyRole(['super_admin', 'admin', 'moderator']),
                403,
                'Analytics access is restricted to administrators and moderators.'
            ),
            'revenue'  => abort_unless(
                $user->hasAnyRole(['super_admin', 'admin']),
                403,
                'Revenue analytics is restricted to administrators.'
            ),
            'employer' => abort_unless(
                $user->hasAnyRole(['super_admin', 'admin', 'moderator', 'employer']),
                403,
                'Employer analytics access is restricted.'
            ),
            default    => abort_unless(
                $user->hasAnyRole(['super_admin', 'admin']),
                403,
                'Access restricted.'
            ),
        };

        return $next($request);
    }
}
