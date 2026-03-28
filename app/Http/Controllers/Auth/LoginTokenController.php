<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\{User, LoginToken};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Mail, Log};
use Illuminate\Support\Str;
use App\Mail\Auth\MagicLoginLink;
use Spatie\Permission\Models\Role;

class LoginTokenController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Show login form
    // ─────────────────────────────────────────────────────────────────────────
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')->with('success', 'You are already logged in.');
        }
        return view('auth.login');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show registration form
    // ─────────────────────────────────────────────────────────────────────────
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')->with('info', 'You are already logged in.');
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

            Log::info('User authenticated via magic link', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Welcome back, ' . $user->full_name . '!');

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
            $defaultRole = Role::where('name', 'job_seeker')->first();

            $user = User::create([
                'first_name'        => $request->first_name,
                'last_name'         => $request->last_name,
                'email'             => $request->email,
                'phone'             => $request->phone,
                'role_id'           => $request->role_id ?? ($defaultRole?->id),
                'country_code'      => $request->country_code ?? 'UG',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            $token = Str::random(64);

            LoginToken::create([
                'user_id'    => $user->id,
                'token'      => $token,
                'expires_at' => now()->addHours(24),
            ]);

            try {
                Mail::to($user->email)->send(new MagicLoginLink($token, true));
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
}