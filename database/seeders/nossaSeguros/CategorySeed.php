<?php

namespace Database\Seeders\nossaSeguros;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use App\Models\IndicatorType;
use Illuminate\Database\Seeder;

class CategorySeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**IndicatorType */
        $IndicatorType = [



            /** Tipo de Actividade Principal Colectiva */

            ['description' => 'Comerciante em nome individual', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 10],
            ['description' => 'Sociedade por quota', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 10],
            ['description' => 'Sociedade anónima', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 10],
            ['description' => 'Cooperativas', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 10],
            ['description' => 'Embaixadas e consulados', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 10],
            ['description' => 'Entidade sem fim lucrativo', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 10],
            ['description' => 'Trust', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 10],
            ['description' => 'Outras', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 10],
            ['description' => 'Banco de fachada', 'risk' => 'Inaceitável', 'score' => 150, 'indicator_id' => 10],
            ['description' => 'Cliente anónimo', 'risk' => 'Inaceitável', 'score' => 150, 'indicator_id' => 10],
            ['description' => 'Cliente fictício', 'risk' => 'Inaceitável', 'score' => 150, 'indicator_id' => 10],

        ];

        //inserindo os indicadores
        foreach ($IndicatorType as $value) {
            IndicatorIndicatorType::create($value);
        }
    }
}
