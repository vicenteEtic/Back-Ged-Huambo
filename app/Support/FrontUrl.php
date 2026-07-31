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

    public static function logo(): string
    {
        $path = public_path('logo_huambo-D4WV4fyp.png');

        if (file_exists($path)) {
            return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
        }

        return self::asset('logo_huambo-D4WV4fyp.png');
    }
}
