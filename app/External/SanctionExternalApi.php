<?php

namespace App\External;

use Illuminate\Support\Facades\Http;

class SanctionExternalApi
{
    public static function getDataSanctionExternal($name)
    {
        $baseUrl = config('services.pep.url');

        $api = Http::get("{$baseUrl}/sanction/search", [
            "filters" => [
                "name"        => $name,
                "min_score"   => 70,
                "limitSearch" => 5
            ]
        ]);

        if ($api->successful()) {
            return $api->json();
        }

        return response()->json(['error' => 'Failed to fetch data from Sanction API'], $api->status());
    }

    public static function getAllSanctions($name = null)
    {
        $baseUrl = config('services.pep.url');

        $data = is_null($name) ? [] : [
            "filters" => [
                "name" => $name
            ]
        ];

        $api = Http::get("{$baseUrl}/sanction", $data);

        if ($api->successful()) {
            return $api->json();
        }

        return response()->json(['error' => 'Failed to fetch data from Sanction API'], $api->status());
    }
}
