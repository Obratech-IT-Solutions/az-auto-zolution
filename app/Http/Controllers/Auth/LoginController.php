<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Apply the guest middleware to all but logout.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login attempt.
     */
    public function login(Request $request)
    {
        // Validate the form data
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Attempt to log the user in
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if (! $user->hasValidStaffRole()) {
                // #region agent log
                file_put_contents(
                    base_path('debug-c4fe64.log'),
                    json_encode([
                        'sessionId' => 'c4fe64',
                        'timestamp' => (int) round(microtime(true) * 1000),
                        'location' => 'LoginController::login',
                        'message' => 'login_rejected_invalid_role',
                        'hypothesisId' => 'H_login_invalid_role',
                        'data' => [
                            'normalizedRole' => $user->normalizedRole(),
                        ],
                    ])."\n",
                    FILE_APPEND | LOCK_EX
                );
                // #endregion

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors([
                        'email' => __('This account is not assigned a valid role. Contact an administrator.'),
                    ])
                    ->withInput($request->only('email'));
            }

            // Redirect to intended or based on role
            return redirect()->intended(
                $user->normalizedRole() === User::ROLE_ADMIN
                    ? route('admin.home')
                    : route('cashier.home')
            );
        }

        // Authentication failed: back with error
        return back()
            ->withErrors(['email' => 'Invalid credentials.'])
            ->withInput();
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
