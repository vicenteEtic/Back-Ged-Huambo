<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $cacheKey = 'user_last_activity_' . $user->id;
            Cache::put($cacheKey, now(), now()->addMinutes(60));
            Log::info("TRACK ACTIVITY: Atualizado timestamp para usuário {$user->id}");
        }

        return $next($request);
    }
}
