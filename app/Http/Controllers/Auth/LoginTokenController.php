<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\{ User, LoginToken };
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Mail};
use Illuminate\Support\Str;
use App\Mail\Auth\{MagicLoginLink};

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

        // Create login token
        $token = Str::random(60);
        $expiresAt = now()->addHours(24);

        LoginToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        // Send magic link email
        try {
            Mail::to($user->email)->send(new MagicLoginLink($token));
            
            return back()->with('success', 'Magic login link has been sent to your email! Please check your inbox.');
        } catch (\Exception $e) {
            \Log::error('Failed to send magic link: ' . $e->getMessage());
            return back()->with('error', 'Unable to send login link at this moment. Please try again later.');
        }
    }

    // Authenticate user with magic link
    public function authenticate($token)
    {
        try {
            $loginToken = LoginToken::with('user')
                ->where('token', $token)
                ->first();

            if (!$loginToken) {
                return redirect()->route('auth.invalid-token')
                    ->with('error', 'Invalid or expired login link.');
            }

            if (!$loginToken->isValid()) {
                return redirect()->route('auth.invalid-token')
                    ->with('error', 'This login link has expired. Please request a new one.');
            }

            // Log the user in
            Auth::login($loginToken->user);

            // Update user's last login
            $loginToken->user->update([
                'last_login_at' => now()
            ]);

            // Mark token as used
            $loginToken->markAsUsed();

            // Clear expired tokens
            $this->clearExpiredTokens($loginToken->user->id);

            return redirect()->route('/dashboard')
                ->with('success', 'Welcome back! You have successfully logged in.');
                
        } catch (\Exception $e) {
            
            \Log::error('Authentication error: ' . $e->getMessage());
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
            'role_id' => 'nullable',
            'country_code' => 'sometimes|string|max:3',
            'terms' => 'required|accepted',
        ], [
            'terms.required' => 'You must agree to the Terms of Service and Privacy Policy.',
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy.',
        ]);

        try {
            // Create user
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role_id' => $request->role_id,
                'country_code' => $request->country_code ?? 'UG',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

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
                
                return redirect()->route('auth.login')
                    ->with('success', 'Account created successfully! Check your email for the magic login link.');
            } catch (\Exception $e) {
                \Log::error('Failed to send welcome email: ' . $e->getMessage());
                return redirect()->route('auth.login')
                    ->with('warning', 'Account created but we couldn\'t send the login link. Please request a new one.');
            }
            
        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
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
        LoginToken::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('expires_at', '<', now())
                    ->orWhereNotNull('used_at');
            })
            ->delete();
    }
}