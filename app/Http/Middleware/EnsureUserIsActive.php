<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * `is_active` is checked when credentials are submitted, but that alone lets a
 * user who is deactivated afterwards keep working until their session expires
 * (or indefinitely, with a remember-me cookie). This re-checks it per request.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Your account has been deactivated.');
            }

            return redirect()->route('login')->withErrors([
                'username' => 'Your account has been deactivated. Contact your administrator.',
            ]);
        }

        return $next($request);
    }
}
