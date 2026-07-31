<?php

namespace App\Support;

use Illuminate\Support\Str;

class FrontUrl
{
    public static function base(): string
    {
        if (app()->runningInConsole()) {
            return rtrim(config('app.url', 'http://localhost'), '/');
        }

        $host = request()->getSchemeAndHttpHost();

        if (empty($host) || $host === '/') {
            return rtrim(config('app.url', 'http://localhost'), '/');
        }

        return rtrim($host, '/');
    }

    public static function frontend(): string
    {
        $origin = request()->headers->get('Origin');

        if (! empty($origin) && Str::startsWith($origin, ['http://', 'https://'])) {
            $parsed = parse_url($origin);

            if (isset($parsed['scheme'], $parsed['host'])) {
                $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

                return $parsed['scheme'].'://'.$parsed['host'].$port;
            }
        }

        if (! empty(config('app.frontend_url'))) {
            return rtrim(config('app.frontend_url'), '/');
        }

        return self::base();
    }

    public static function resetPasswordUrl(string $token, string $email): string
    {
        return self::frontend().'/reset-password?token='.$token.'&email='.urlencode($email);
    }

    public static function asset(string $path): string
    {
        return self::frontend().'/'.ltrim($path, '/');
    }
}
