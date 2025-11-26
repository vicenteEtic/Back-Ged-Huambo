<?php

namespace App\External;

use Illuminate\Support\Facades\Http;

class PepExternalApi
{
    public static function getDataPepExternal($name)
    {
        $baseUrl = config('services.pep.url');

        $api = Http::get("{$baseUrl}/pep/search", [
            "filters" => [
                "name"       => $name,
                "min_score"  => 70,
                "limitSearch"=> 5
            ]
        ]);

        if ($api->successful()) {
            return $api->json();
        }

        return response()->json(['error' => 'Failed to fetch data from PEP API'], $api->status());
    }

    public static function getAllPep($name = null)
    {
        $baseUrl = config('services.pep.url');

        $data = is_null($name) ? [] : [
            "filters" => [
                "name" => $name
            ]
        ];

        $api = Http::get("{$baseUrl}/pep", $data);

        if ($api->successful()) {
            return $api->json();
        }

        return response()->json(['error' => 'Failed to fetch data from PEP API'], $api->status());
    }
}
