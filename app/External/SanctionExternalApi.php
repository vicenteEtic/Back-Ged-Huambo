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

        // Caminho para o certificado da CA (opcional, se usar certificado autoassinado)
        $caCertPath = '/etc/ssl/certs/listapeps-ca.crt'; // ajuste conforme necessário

        $api = Http::timeout(60)
            ->retry(3, 1000)
            ->withOptions([
                'verify' => file_exists($caCertPath) ? $caCertPath : false // fallback para teste
            ])
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

        return response()->json([
            'error' => 'Failed to fetch data from Sanction API',
            'status' => $api->status()
        ], $api->status());
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

        $caCertPath = '/etc/ssl/certs/listapeps-ca.crt'; // ajuste conforme necessário

        $api = Http::timeout(60)
            ->retry(3, 1000)
            ->withOptions([
                'verify' => file_exists($caCertPath) ? $caCertPath : false
            ])
            ->get("{$baseUrl}/sanction", $data);

        if ($api->successful()) {
            return $api->json();
        }

        return response()->json([
            'error' => 'Failed to fetch data from Sanction API',
            'status' => $api->status()
        ], $api->status());
    }
}
