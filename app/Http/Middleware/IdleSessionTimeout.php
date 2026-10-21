<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdleSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $timeout = (int) config('session.lifetime', 30);
            $lastActivity = (int) $request->session()->get('user.last_activity_at', now()->timestamp);

            if ($timeout > 0 && (now()->timestamp - $lastActivity) >= ($timeout * 60)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('filament.admin.auth.login');
            }

            $request->session()->put('user.last_activity_at', now()->timestamp);
        }

        return $next($request);
    }
}
