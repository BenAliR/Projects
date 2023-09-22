<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class IsUserBanned
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->check() && auth()->user()->banned != null) {
            $message = '';
            if (auth()->user()->banned == 0) {
                $message = 'Votre compte a été définitivement banni.';
            }
            if (now()->lessThan(auth()->user()->banned)) {
                $banned_days = now()->diffInDays(auth()->user()->banned) + 1;
                $message = 'Votre compte a été suspendu pour ' . $banned_days . ' ' . Str::plural('jour', $banned_days);
            }

            auth()->logout();
            $user = auth()->user()->token();
            $user->revoke();
            return response()->json(['error' => $message], 400);
        }

        return $next($request);
    }
}
