<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;


use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;


class LastSeenUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user()) {
         //   $expireTime = Carbon::now()->addMinute(1); // keep online for 1 min
         //   Cache::put('is_online'.$request->user()->id, true, $expireTime);

            //Last Seen
            User::where('id', $request->user()->id)->update(['last_seen' => Carbon::now()]);
        }
        return $next($request);
    }
}
