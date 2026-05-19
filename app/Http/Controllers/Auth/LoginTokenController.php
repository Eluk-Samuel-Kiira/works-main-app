<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\{User, LoginToken};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Auth, Mail, Log, Artisan};
use Illuminate\Support\Str;
use App\Mail\Auth\{ MagicLoginLink, WebMagicLoginLink };
use Spatie\Permission\Models\Role;

class LoginTokenController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Show login form
    // ─────────────────────────────────────────────────────────────────────────
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('analytics.dashboard')->with('success', 'You are already logged in.');
        }
        return view('auth.login');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show registration form
    // ─────────────────────────────────────────────────────────────────────────
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('analytics.dashboard')->with('info', 'You are already logged in.');
        }
        return view('auth.register');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Send magic login link
    // ─────────────────────────────────────────────────────────────────────────
    public function sendLoginLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $email = $request->email;
        $user  = User::where('email', $email)->first();

        if (!$user) {
            return back()->with('error', 'No account found with this email address. Please register first.');
        }

        if (!$user->is_active) {
            return back()->with('error', 'Your account is deactivated. Please contact support.');
        }

        // Delete any existing unused tokens
        LoginToken::where('user_id', $user->id)->whereNull('used_at')->delete();

        $token     = Str::random(64);
        $expiresAt = now()->addHours(24);

        try {
            LoginToken::create([
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => $expiresAt,
            ]);

            Mail::to($user->email)->send(new MagicLoginLink($token));

            return back()->with('success', 'Magic login link sent! Please check your inbox.');

        } catch (\Exception $e) {
            Log::error('Failed to create token or send email: ' . $e->getMessage());
            return back()->with('error', 'Unable to send login link at this moment. Please try again later.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Authenticate user via magic link token
    // ─────────────────────────────────────────────────────────────────────────
    public function authenticate(Request $request, $token)
    {
        try {
            $loginToken = LoginToken::with('user')
                ->where('token', $token)
                ->first();

            // Token not found
            if (!$loginToken) {
                Log::warning('Login attempt with invalid token', ['token' => substr($token, 0, 8) . '...']);
                return redirect()->route('auth.invalid-token')
                    ->with('error', 'Invalid or expired login link.');
            }

            // User missing (orphaned token)
            if (!$loginToken->user) {
                Log::error('Orphaned login token', ['token_id' => $loginToken->id]);
                $loginToken->delete();
                return redirect()->route('auth.invalid-token')
                    ->with('error', 'This login link is invalid. Please request a new one.');
            }

            // User inactive
            if (!$loginToken->user->is_active) {
                return redirect()->route('login')
                    ->with('error', 'Your account has been deactivated. Please contact support.');
            }

            // Token already used or expired
            if (!$loginToken->isValid()) {
                $reason = $loginToken->used_at ? 'already been used' : 'expired';
                $loginToken->delete();
                return redirect()->route('auth.invalid-token')
                    ->with('error', "This login link has {$reason}. Please request a new one.");
            }

            $user = $loginToken->user;

            // ── 1. Log the user in via the web guard (session-based) ──────────
            Auth::guard('web')->login($user, true); // true = remember me

            // ── 2. Regenerate session to prevent session fixation ─────────────
            $request->session()->regenerate();

            // ── 3. Store user ID explicitly in session (belt-and-suspenders) ──
            $request->session()->put('auth.user_id', $user->id);
            $request->session()->put('auth.logged_in_at', now()->toISOString());

            // ── 4. Issue a Sanctum personal access token for API calls ────────
            //       Delete old tokens first so we don't accumulate them
            $user->tokens()->delete();

            $apiToken = $user->createToken(
                'web-session',
                ['*'],                          // abilities
                now()->addDays(30)              // expiry matches session lifetime
            )->plainTextToken;

            // Store the API token in the session so JS/API calls can read it
            $request->session()->put('api_token', $apiToken);

            // ── 5. Update user record ─────────────────────────────────────────
            $user->update(['last_login_at' => now()]);

            // ── 6. Mark magic link token as used ─────────────────────────────
            $loginToken->markAsUsed();

            // ── 7. Clean up old expired tokens ───────────────────────────────
            $this->clearExpiredTokens($user->id);

            \Artisan::call('optimize:clear');
            Log::info('User authenticated via magic link', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            // ── 8. Redirect based on role ─────────────────────────────────────
            $welcome = 'Welcome back, ' . $user->full_name . '!';

            $welcome = 'Welcome back, ' . $user->full_name . '!';

            $welcome = 'Welcome back, ' . $user->full_name . '!';

            if ($user->hasRole('employer')) {
                return redirect('/analytics/employer')->with('success', $welcome);
            }

            if ($user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('moderator')) {
                return redirect('/analytics')->with('success', $welcome);
            }

            // fallback
            return redirect('/dashboard')->with('success', $welcome);

        } catch (\Exception $e) {
            Log::error('Authentication error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('login')
                ->with('error', 'An error occurred during authentication. Please try again.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Register
    // ─────────────────────────────────────────────────────────────────────────
    public function register(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users',
            'phone'        => 'nullable|string|max:20',
            'company'      => 'nullable|string|max:255',
            'role_id'      => 'nullable|exists:roles,id',
            'country_code' => 'sometimes|string|max:3',
            'terms'        => 'required|accepted',
        ], [
            'terms.required' => 'You must agree to the Terms of Service and Privacy Policy.',
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
            'role_id.exists' => 'The selected role is invalid.',
        ]);

        try {
            // ── 1. Resolve the role first, before user creation ──────────────
            $role = $request->role_id
                ? Role::find($request->role_id)
                : Role::where('name', 'job_seeker')->first();

            // Fallback safety — if somehow role still null
            if (!$role) {
                $role = Role::where('name', 'job_seeker')->firstOrFail();
            }

            // ── 2. Create the user with the confirmed role_id ─────────────────
            $user = User::create([
                'first_name'        => $request->first_name,
                'last_name'         => $request->last_name,
                'email'             => $request->email,
                'phone'             => $request->phone,
                'role_id'           => $role->id,
                'country_code'      => $request->country_code ?? 'UG',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            // ── 3. Explicitly assign Spatie role ──────────────────────────────
            //    syncRoles ensures no stale roles from previous state
            $user->syncRoles([$role->name]);

            Log::info('User registered and Spatie role assigned', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'role'    => $role->name,
            ]);

            // ── 4. Create magic link token ────────────────────────────────────
            $token = Str::random(64);

            LoginToken::create([
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => now()->addHours(24),
            ]);

            // ── 5. Send correct magic link based on role ──────────────────────
            try {
                if ($user->isJobSeeker()) {
                    // Job seekers → works-web
                    Mail::to($user->email)->send(new WebMagicLoginLink($token, true));
                } else {
                    // Employers and any other role → works-main admin dashboard
                    Mail::to($user->email)->send(new MagicLoginLink($token, true));
                }

                return redirect()->route('login')
                    ->with('success', 'Account created! Check your email for the magic login link.');

            } catch (\Exception $e) {
                Log::error('Failed to send welcome email: ' . $e->getMessage());
                return redirect()->route('login')
                    ->with('warning', 'Account created but we couldn\'t send the login link. Please request a new one.');
            }

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Unable to create account. Please try again.');
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Logout
    // ─────────────────────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        // Revoke all Sanctum tokens for this user
        if (Auth::check()) {
            Auth::user()->tokens()->delete();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('info', 'You have been logged out successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Invalid token page
    // ─────────────────────────────────────────────────────────────────────────
    public function invalidToken()
    {
        return view('auth.invalid-token');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function clearExpiredTokens(int $userId): void
    {
        try {
            LoginToken::where('user_id', $userId)
                ->where(function ($q) {
                    $q->where('expires_at', '<', now())
                      ->orWhereNotNull('used_at');
                })
                ->delete();
        } catch (\Exception $e) {
            Log::error('Error clearing expired tokens: ' . $e->getMessage());
        }
    }



    
    // ─────────────────────────────────────────────────────────────────────────
    // API: Send magic link  →  link goes to works-web
    // POST /api/auth/send-login-link
    // ─────────────────────────────────────────────────────────────────────────



    public function sendLoginLinkApi(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|max:255']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address. Please register first.',
            ], 404);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is deactivated. Please contact support.',
            ], 403);
        }

        // Delete existing unused tokens for this user
        LoginToken::where('user_id', $user->id)->whereNull('used_at')->delete();

        $token = Str::random(64);

        LoginToken::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => now()->addHours(24),
        ]);

        try {
            if ($user->isJobSeeker()) {
                // Job seekers stay on works-web
                Mail::to($user->email)->send(new WebMagicLoginLink($token));
            } else {
                // Employers, admins, moderators, support → works-main admin dashboard
                Mail::to($user->email)->send(new MagicLoginLink($token));
            }

            return response()->json(['success' => true, 'message' => 'Magic link sent.']);

        } catch (\Exception $e) {
            Log::error('Magic link send failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to send email right now. Please try again.',
            ], 500);
        }
    }

 
    // ─────────────────────────────────────────────────────────────────────────
    // API: Register seeker / employer  →  link goes to works-web
    // POST /api/auth/register
    // ─────────────────────────────────────────────────────────────────────────
    public function registerApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|max:255|unique:users,email',
            'phone'        => 'nullable|string|max:25',
            'role'         => 'nullable|string|in:job_seeker,employer',
            'country_code' => 'nullable|string|max:3',
            'terms'        => 'required|accepted',
        ], [
            'email.unique'   => 'An account with this email already exists. Try logging in instead.',
            'terms.accepted' => 'You must accept the Terms of Service to continue.',
        ]);

        try {
            // ── 1. Resolve role — only job_seeker or employer allowed here ────
            $roleName = $validated['role'] ?? 'job_seeker';
            $role     = Role::where('name', $roleName)->first()
                    ?? Role::where('name', 'job_seeker')->firstOrFail();

            // ── 2. Create user with confirmed role_id ─────────────────────────
            $user = User::create([
                'first_name'        => $validated['first_name'],
                'last_name'         => $validated['last_name'],
                'email'             => $validated['email'],
                'phone'             => $validated['phone'] ?? null,
                'role_id'           => $role->id,
                'country_code'      => $validated['country_code'] ?? 'UG',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            // ── 3. Explicitly assign Spatie role ──────────────────────────────
            $user->syncRoles([$role->name]);

            Log::info('Web API user registered and Spatie role assigned', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'role'    => $role->name,
            ]);

            // ── 4. Create magic link token ────────────────────────────────────
            $token = Str::random(64);

            LoginToken::create([
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => now()->addHours(24),
            ]);

            // ── 5. Route mail based on role ───────────────────────────────────
            //    job_seeker → works-web   |   employer → works-main dashboard
            if ($user->isJobSeeker()) {
                Mail::to($user->email)->send(new WebMagicLoginLink($token, true));
            } else {
                Mail::to($user->email)->send(new MagicLoginLink($token, true));
            }

            return response()->json(['success' => true, 'message' => 'Account created. Check your email.']);

        } catch (\Exception $e) {
            Log::error('Web registration API error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to create account. Please try again.',
            ], 500);
        }
    }
 
    
    // ─────────────────────────────────────────────────────────────────────────
    // API: Verify token  →  called by works-web after user clicks the link
    // POST /api/auth/verify-token
    //
    // Returns the user data so works-web can build its own session.
    // Does NOT log anyone into works-main.
    // ─────────────────────────────────────────────────────────────────────────
    public function verifyTokenApi(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string|size:64']);
 
        $loginToken = LoginToken::with('user')->where('token', $request->token)->first();
 
        if (!$loginToken || !$loginToken->user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired magic link.',
            ], 401);
        }
 
        if (!$loginToken->isValid()) {
            $reason = $loginToken->used_at ? 'already been used' : 'expired';
            $loginToken->delete();
 
            return response()->json([
                'success' => false,
                'message' => "This link has {$reason}. Please request a new one.",
            ], 401);
        }
 
        if (!$loginToken->user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This account has been deactivated. Please contact support.',
            ], 403);
        }
 
        $user = $loginToken->user;
 
        // Mark token used + update last_login
        $loginToken->markAsUsed();
        $user->update(['last_login_at' => now()]);
 
        // Clean up stale tokens
        LoginToken::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('expires_at', '<', now())->orWhereNotNull('used_at');
            })
            ->delete();
 
        // Return user payload — works-web creates the session from this
        return response()->json([
            'success' => true,
            'user'    => [
                'id'         => $user->id,
                'uuid'       => $user->uuid,
                'email'      => $user->email,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'full_name'  => $user->full_name,
                'phone'      => $user->phone,
                'role'       => $user->role?->name,
                'role_id'    => $user->role_id,
                'country_code' => $user->country_code,
                'is_active'  => $user->is_active,
                'last_login_at' => $user->last_login_at,
            ],
        ]);
    }

}