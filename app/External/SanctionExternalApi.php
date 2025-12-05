<?php

namespace App\External;

use Illuminate\Support\Facades\Http;

class SanctionExternalApi
{
    /**
     * Buscar dados de Sanctions por nome
     */
    public static function getDataSanctionExternal($name)
    {
        $baseUrl = config('services.pep.url');

        $api = Http::withOptions(['verify' => false]) // Ignora verificação SSL
            ->timeout(60)
            ->retry(3, 1000)
            ->get("{$baseUrl}/sanction/search", [
                "filters" => [
                    "name"        => $name,
                    "min_score"   => 70,
                    "limitSearch" => 5
                ]
            ]);

        if ($api->successful()) {
            return $api->json();
        }

        return [
            'error'  => 'Failed to fetch data from Sanction API',
            'status' => $api->status()
        ];
    }

    /**
     * Buscar todos os dados de Sanctions ou filtrados por nome
     */
    public static function getAllSanctions($name = null)
    {
        $baseUrl = config('services.pep.url');

        $data = is_null($name) ? [] : [
            "filters" => [
                "name" => $name
            ]
        ];

        $api = Http::withOptions(['verify' => false]) // Ignora verificação SSL
            ->timeout(60)
            ->retry(3, 1000)
            ->get("{$baseUrl}/sanction", $data);

        if ($api->successful()) {
            return $api->json();
        }

        return [
            'error'  => 'Failed to fetch data from Sanction API',
            'status' => $api->status()
        ];
    }
}
