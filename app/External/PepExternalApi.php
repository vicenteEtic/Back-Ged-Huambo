<?php

namespace App\External;

use Illuminate\Support\Facades\Http;

class PepExternalApi
{
    /**
     * Buscar dados de PEP por nome
     */
    public static function getDataPepExternal($name)
    {
        $baseUrl = config('services.pep.url');

        // Timeout de 60 segundos, retry 3 vezes e ignorar verificação SSL
        $api = Http::timeout(60)
            ->retry(5, 1000)
            ->withOptions(['verify' => false])
            ->get("{$baseUrl}/pep/search", [
                "filters" => [
                    "name"       => $name,
                    "min_score"  => 70,
                    "limitSearch"=> 5
                ]
            ]);

        if ($api->successful()) {
            return $api->json();
        }

        return response()->json([
            'error' => 'Failed to fetch data from PEP API',
            'status' => $api->status()
        ], $api->status());
    }

    /**
     * Buscar todos os dados de PEP ou filtrados por nome
     */
    public static function getAllPep($name = null)
    {
        $baseUrl = config('services.pep.url');

        $data = is_null($name) ? [] : [
            "filters" => [
                "name" => $name
            ]
        ];

        $api = Http::timeout(60)
            ->retry(3, 1000)
            ->withOptions(['verify' => false])
            ->get("{$baseUrl}/pep", $data);

        if ($api->successful()) {
            return $api->json();
        }

        return response()->json([
            'error' => 'Failed to fetch data from PEP API',
            'status' => $api->status()
        ], $api->status());
    }
}
