<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\{ User, LoginToken };
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Mail, DB};
use Illuminate\Support\Str;
use App\Mail\Auth\{MagicLoginLink};
use Spatie\Permission\Models\Role; // Import Spatie's Role model

class LoginTokenController extends Controller
{
    // Show login form
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')->with('success', 'You are already logged in.');
        }
        return view('auth.login');
    }

    // Show registration form
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')->with('info', 'You are already logged in.');
        }
        return view('auth.register');
    }

    // Send magic login link
    public function sendLoginLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $email = $request->email;
        
        // Check if user exists
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->with('error', 'No account found with this email address. Please register first.');
        }

        // Check if user is active
        if (!$user->is_active) {
            return back()->with('error', 'Your account is deactivated. Please contact support.');
        }

        // Delete any existing unused tokens for this user
        LoginToken::where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        // Create login token
        $token = Str::random(60);
        $expiresAt = now()->addHours(24);

        try {
            LoginToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);

            // Send magic link email
            Mail::to($user->email)->send(new MagicLoginLink($token));
            
            return back()->with('success', 'Magic login link has been sent to your email! Please check your inbox.');
            
        } catch (\Exception $e) {
            \Log::error('Failed to create token or send email: ' . $e->getMessage());
            return back()->with('error', 'Unable to send login link at this moment. Please try again later.');
        }
    }

    // Authenticate user with magic link
    public function authenticate($token)
    {
        try {
            // First, find the token with user relationship
            $loginToken = LoginToken::with('user')
                ->where('token', $token)
                ->first();

            // Check if token exists
            if (!$loginToken) {
                \Log::warning('Login attempt with invalid token', ['token' => $token]);
                return redirect()->route('auth.invalid-token')
                    ->with('error', 'Invalid or expired login link.');
            }

            // Log token details for debugging
            \Log::info('Login token found', [
                'token_id' => $loginToken->id,
                'user_id' => $loginToken->user_id,
                'expires_at' => $loginToken->expires_at,
                'used_at' => $loginToken->used_at
            ]);

            // Check if user exists
            if (!$loginToken->user) {
                \Log::error('Login token exists but user not found', [
                    'token_id' => $loginToken->id,
                    'user_id' => $loginToken->user_id
                ]);
                
                // Delete the orphaned token
                $loginToken->delete();
                
                return redirect()->route('auth.invalid-token')
                    ->with('error', 'This login link is invalid. The associated user account could not be found.');
            }

            // Check if user is active
            if (!$loginToken->user->is_active) {
                \Log::warning('Login attempt for inactive user', [
                    'user_id' => $loginToken->user->id,
                    'email' => $loginToken->user->email
                ]);
                
                return redirect()->route('login')
                    ->with('error', 'Your account has been deactivated. Please contact support.');
            }

            // Check if token is valid
            if (!$loginToken->isValid()) {
                $reason = $loginToken->used_at ? 'already used' : 'expired';
                \Log::info('Token validation failed', [
                    'token_id' => $loginToken->id,
                    'reason' => $reason
                ]);
                
                // Delete the invalid token
                $loginToken->delete();
                
                return redirect()->route('auth.invalid-token')
                    ->with('error', 'This login link has ' . ($reason === 'expired' ? 'expired' : 'already been used') . '. Please request a new one.');
            }

            // Log the user in
            Auth::login($loginToken->user);

            // Update user's last login
            $loginToken->user->update([
                'last_login_at' => now()
            ]);

            // Mark token as used
            $loginToken->markAsUsed();

            // Clear expired tokens for this user
            $this->clearExpiredTokens($loginToken->user->id);

            // Redirect to dashboard with success message
            return redirect()->route('dashboard')
                ->with('success', 'Welcome back ' . $loginToken->user->full_name . '! You have successfully logged in.');
                
        } catch (\Exception $e) {
            \Log::error('Authentication error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('login')
                ->with('error', 'An error occurred during authentication. Please try again.');
        }
    }
    
    // User registration
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'role_id' => 'nullable|exists:roles,id',
            'country_code' => 'sometimes|string|max:3',
            'terms' => 'required|accepted',
        ], [
            'terms.required' => 'You must agree to the Terms of Service and Privacy Policy.',
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
            'role_id.exists' => 'The selected role is invalid.',
        ]);

        try {
            // Get default role (job_seeker) if not provided
            $defaultRole = Role::where('name', 'job_seeker')->first();
            
            // Create user with default role_id if not provided
            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => $request->role_id ?? ($defaultRole ? $defaultRole->id : null),
                'country_code' => $request->country_code ?? 'UG',
                'is_active' => true,
                'email_verified_at' => now(),
            ];

            $user = User::create($userData);

            // Create login token
            $token = Str::random(60);
            $expiresAt = now()->addHours(24);

            LoginToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);

            // Send welcome email with magic link
            try {
                Mail::to($user->email)->send(new MagicLoginLink($token, true));
                
                return redirect()->route('login')
                    ->with('success', 'Account created successfully! Check your email for the magic login link.');
            } catch (\Exception $e) {
                \Log::error('Failed to send welcome email: ' . $e->getMessage());
                return redirect()->route('login')
                    ->with('warning', 'Account created but we couldn\'t send the login link. Please request a new one.');
            }
            
        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            \Log::error('Registration error details: ' . $e->getTraceAsString());
            return back()->withInput()
                ->with('error', 'Unable to create account. Please try again.');
        }
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('info', 'You have been logged out successfully.');
    }

    // Invalid token page
    public function invalidToken()
    {
        return view('auth.invalid-token');
    }

    // Clear expired tokens for a user
    private function clearExpiredTokens($userId)
    {
        try {
            LoginToken::where('user_id', $userId)
                ->where(function ($query) {
                    $query->where('expires_at', '<', now())
                        ->orWhereNotNull('used_at');
                })
                ->delete();
        } catch (\Exception $e) {
            \Log::error('Error clearing expired tokens: ' . $e->getMessage());
        }
    }
}