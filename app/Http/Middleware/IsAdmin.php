<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user() &&  Auth::user()->role == 'monitor') {
            return $next($request);
        }
        return response()->json([
            'status'=>false,
            'message' => 'non autorisé.',
            'data' => ['non autorisé.'],
            'errors' =>['non autorisé.']
        ], 401);

    }
}
