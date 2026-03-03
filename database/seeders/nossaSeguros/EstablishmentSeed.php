<?php

namespace Database\Seeders\nossaSeguros;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;

use Illuminate\Database\Seeder;

class EstablishmentSeed extends Seeder
{
    public function run()
    {
           /**IndicatorType */
           $IndicatorType = [

           /**Forma de Estabelecimento de relação de negócio */
           ['description' => "Presencial",  "risk" => "Baixo", "score" => "1", "indicator_id" => 2],
           ['description' => "Não Presencial (Internet/ Mobile / App)",  "risk" => "Alto", "score" => "3", "indicator_id" => 2],
        ];

        //inserindo os indicadores
        foreach ($IndicatorType as $value) {
            if (!IndicatorIndicatorType::where('description', $value['description'])->exists()) {
                IndicatorIndicatorType::create($value);
            }
        }
    }

}
