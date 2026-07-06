<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class Can
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, ...$permissoes)
    {
        $missing = self::getMissingPermissions($permissoes);

        if (empty($missing)) {
            return $next($request);
        }

        $permStr = implode(', ', $missing);
        return response()->json(['error' => "Acesso negado. Permissão necessária: {$permStr}."], Response::HTTP_FORBIDDEN);
    }

    private function getMissingPermissions(array $permissions): array
    {
        $missing = [];

        foreach ($permissions as $permissionSet) {
            if (str_contains($permissionSet, '|')) {
                $orPermissions = explode('|', $permissionSet);
                $hasAtLeastOne = false;

                foreach ($orPermissions as $permission) {
                    if (self::check($permission)) {
                        $hasAtLeastOne = true;
                        break;
                    }
                }

                if (!$hasAtLeastOne) {
                    $missing[] = $permissionSet;
                }
            } else {
                if (!self::check($permissionSet)) {
                    $missing[] = $permissionSet;
                }
            }
        }

        return $missing;
    }

    /**
     * Verifica se o usuário da requisição atual tem a permissão informada.
     */
    public static function check(?string $rule): bool
    {
        if ($rule == null) {
            return false;
        }

        if (Auth::user()) {
            return Auth::user()->can($rule);
        }

        return false;
    }
}
