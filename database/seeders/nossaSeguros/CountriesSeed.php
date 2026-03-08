<?php

namespace Database\Seeders\nossaSeguros;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use App\Models\IndicatorType;
use Illuminate\Database\Seeder;

class CountriesSeed extends Seeder
{
    public function run()
    {
           /**IndicatorType */
           $IndicatorType = [


            /**Risco Pais / País de Registo da entidade*/
        ['description' => 'AFEGANISTÃO', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ÁFRICA DO SUL', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ALBANIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ALEMANHA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'ANDORRA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ANGOLA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'ANGUILA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ANTÁRTICA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'ANTÍGUA E BARBUDA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ANTILHAS HOLANDESAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ARÁBIA SAUDITA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'ARGÉLIA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'ARGENTINA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'ARMENIA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'ARUBA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'AUSTRÁLIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'AUSTRIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'AZERBEIJÃO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'BAHAMAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BAHRAIN', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BANGLADESH', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'BARBADOS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BÉLGICA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'BELIZE', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BENIN', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'BERMUDAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BIELORÚSSIA (BELARUS)', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BOLÍVIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BOSNIA-HERZEGOVINA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BOTSWANA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'BOUVET, ILHA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'BRASIL', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'BRUNEI DARUSSALAM', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BULGÁRIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BURKINA FASO', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BURUNDI', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'BUTÃO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'CABO VERDE', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'CAIMANS, ILHAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'CAMARÕES', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'CAMBODJA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'CANADÁ', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'CASAQUISTÃO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'CENTRO-AFRICANA, REPÚBLICA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'CHADE', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'CHILE', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'CHINA, REPÚBLICA POPULAR', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'COREIA, REP.POP.DEMOCR. (NORTE)', 'risk' => 'Inaceitável', 'score' => 1000, 'indicator_id' => 9],
            ['description' => 'COREIA, REPÚBLICA (SUL)', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'COSTA DO MARFIM', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'COSTA RICA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'CROÁCIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'CUBA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'DINAMARCA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'DJIBOUTI', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'DOMINICA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'DOMINICANA, REPÚBLICA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'EGIPTO', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'EL SALVADOR', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'EMIRATOS ÁRABES UNIDOS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ESTADOS UNIDOS DA AMÉRICA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'FRANÇA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'PORTUGAL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'REINO UNIDO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'RUSSIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'VENEZUELA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
            ['description' => 'VIETNAME', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
            ['description' => 'ZÂMBIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
            ['description' => 'ZIMBABWE', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
];
 



        //inserindo os indicadores
        foreach ($IndicatorType as $value) {
            if (!IndicatorIndicatorType::where('description', $value['description'])->exists()) {
                IndicatorIndicatorType::create($value);
            }
        }

    }

}
