<?php

namespace Database\Seeders;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use App\Models\IndicatorType;
use Illuminate\Database\Seeder;

class ResidenceSeed extends Seeder
{
    public function run()
    {
           /**IndicatorType */
           $IndicatorType = [

            /*Residência* */
            ['description' => "Residente",  "risk" => "Baixo", "score" => "1", "indicator_id" => 4],
            ['description' => "Estrangeiro",  "risk" => "Alto", "score" => "3", "indicator_id" => 4],

        ];

        //inserindo os indicadores
        foreach ($IndicatorType as $value) {
            if (!IndicatorIndicatorType::where('description', $value['description'])->exists()) {
                IndicatorIndicatorType::create($value);
            }
        }
    }

}
