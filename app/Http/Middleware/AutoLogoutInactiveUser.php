<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoLogoutInactiveUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $cacheKey = 'user_last_activity_' . $user->id;
            $lastActivity = Cache::get($cacheKey);
          // Tempo máximo de inatividade (em minutos) — configurável no .env
            $timeout = env('AUTO_LOGOUT_TIMEOUT', 15);
            if ($lastActivity) {
                // Garante que é um Carbon válido (se vier como string)
                if (is_string($lastActivity)) {
                    $lastActivity = Carbon::parse($lastActivity);
                }
                // Corrige a ordem da diferença
                $diff = $lastActivity->diffInMinutes(now());
                Log::info("AUTO LOGOUT: Última atividade há {$diff} minutos (timeout {$timeout})");

                if ($diff >= $timeout) {
                    $user->tokens()->delete();
                    Cache::forget($cacheKey);
                    Log::info("AUTO LOGOUT: Token revogado por inatividade do usuário {$user->id}");

                    return response()->json([
                        'message' => 'Sessão expirada por inatividade.'
                    ], 401);
                }
            } else {
                Log::info("AUTO LOGOUT: Nenhuma atividade encontrada para usuário {$user->id}");
            }
        }

        return $next($request);
    }
}
