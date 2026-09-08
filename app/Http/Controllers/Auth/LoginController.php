<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    protected $redirectTo = '/dashboard';

    /**
     * Failed attempts allowed per username + IP before the form locks out.
     */
    protected const MAX_ATTEMPTS = 5;
    protected const DECAY_SECONDS = 60;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);
        $this->ensureIsNotRateLimited($request);

        if ($this->attemptLogin($request)) {
            RateLimiter::clear($this->throttleKey($request));

            // Guards against session fixation: the pre-login id is discarded.
            $request->session()->regenerate();

            return $this->authenticated($request, Auth::user());
        }

        RateLimiter::hit($this->throttleKey($request), self::DECAY_SECONDS);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Per username + IP, so one attacker cannot lock every account out and a
     * botnet cannot spread a single account's guesses across many addresses.
     */
    protected function throttleKey(Request $request): string
    {
        return 'login|' . Str::transliterate(Str::lower((string) $request->input('username'))) . '|' . $request->ip();
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Validate the user login request.
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Attempt to log the user into the application.
     */
    protected function attemptLogin(Request $request)
    {
        return Auth::attempt(
            $this->credentials($request),
            $request->boolean('remember')
        );
    }

    /**
     * Get the needed authorization credentials from the request.
     */
    protected function credentials(Request $request)
    {
        $login = $request->input('username');

        // The single sign-in field accepts either a username or an email address.
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $request->input('password'),
            'is_active' => true, // Only allow active users to login
        ];
    }

    /**
     * The user has been authenticated.
     */
    protected function authenticated(Request $request, $user)
    {
        // Update last login time
        $user->update(['last_login_at' => now()]);

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the failed login response instance.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'username' => ['These credentials do not match our records.'],
        ]);
    }

    /**
     * Get the login username to be used by the controller.
     */
    public function username()
    {
        return 'username';
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();


        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Get the guard to be used during authentication.
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * Get the post-register / post-login redirect path.
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/dashboard';
    }
}
