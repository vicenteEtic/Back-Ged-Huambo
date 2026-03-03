<?php

namespace Database\Seeders;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use App\Models\IndicatorType;
use Illuminate\Database\Seeder;

class LegalFormSeed extends Seeder
{
    public function run()
    {
        /**IndicatorType */
        $IndicatorType = [
            /**Forma Juridica da entidade*/
               ['description' => 'Comerciante em nome individual', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 3],
            ['description' => 'Sociedade por quota', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 3],
            ['description' => 'Sociedade anónima', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 3],
            ['description' => 'Cooperativas', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 3],
            ['description' => 'Embaixadas e consulados', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 3],
            ['description' => 'Entidade sem fim lucrativo', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 3],
            ['description' => 'Trust', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 3],
            ['description' => 'Outras', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 3],
            ['description' => 'Banco de fachada', 'risk' => 'Inaceitável', 'score' => 150, 'indicator_id' => 3],
            ['description' => 'Cliente anónimo', 'risk' => 'Inaceitável', 'score' => 150, 'indicator_id' => 3],
            ['description' => 'Cliente fictício', 'risk' => 'Inaceitável', 'score' => 150, 'indicator_id' => 3],


            //Forma Juridica da entidade
        ];

        //inserindo os indicadores
        foreach ($IndicatorType as $value) {
            if (!IndicatorIndicatorType::where('description', $value['description'])->exists()) {
                IndicatorIndicatorType::create($value);
            }
        }
    }
}
