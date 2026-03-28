<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Auth\User;

/**
 * ApiBaseController
 *
 * Extend this in any API controller that needs to know the current user.
 * It resolves the user from three sources in priority order:
 *
 *   1. Sanctum personal access token  (Authorization: Bearer <token>)
 *   2. Web session  (when the API is called same-domain via fetch/axios)
 *   3. X-User-Id header  (fallback for internal service-to-service calls)
 */
class ApiBaseController extends Controller
{
    /**
     * Resolve the authenticated user for this request.
     * Returns null if no valid auth is present.
     */
    protected function resolveUser(Request $request): ?User
    {
        // ── 1. Bearer token (Sanctum PAT) ─────────────────────────────────
        if ($request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
            if ($user) return $user;
        }

        // ── 2. Web session (same-origin fetch with cookies) ───────────────
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }

        // ── 3. Session user_id fallback ───────────────────────────────────
        $sessionUserId = $request->session()->get('auth.user_id');
        if ($sessionUserId) {
            return User::find($sessionUserId);
        }

        return null;
    }

    /**
     * Return a 401 JSON response with a descriptive message.
     */
    protected function unauthenticated(string $message = 'Unauthenticated.'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'user'    => null,
        ], 401);
    }
}