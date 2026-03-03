<?php

namespace Database\Seeders;

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
    ['description' => 'AFRICA DO SUL', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ALBANIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ALEMANHA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ANDORRA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ANGOLA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ANTÍGUA E BARBUDA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ANTILHAS HOLANDESAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ARÁBIA SAUDITA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'ARGÉLIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ARGENTINA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'ARMÉNIA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'ARUBA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ASCENSÃO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'AUSTRÁLIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ÁUSTRIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'AZERBAIJÃO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'BAHAMAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BAHRAIN', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BANGLADESH', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'BARBADOS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BÉLGICA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'BELIZE', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BENIN', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'BERMUDAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BIELORRÚSSIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BOLÍVIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BÓSNIA E HERZEGOVINA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BOTSWANA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'BRASIL', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'BRUNEI', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BULGÁRIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BURKINA FASO', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BURUNDI', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'BUTÃO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'CAMARÕES', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'CAMBOJA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'CANADÁ', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'CAZAQUISTÃO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'CHADE', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'CHECOSLOVÁQUIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'CHILE', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'REPÚBLICA POPULAR DA CHINA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'CHIPRE', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'COLÔMBIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'COMORES', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'COSTA DO MARFIM', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'COSTA RICA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],

    ['description' => 'CROÁCIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'CUBA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'CURAÇAU', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'DESCONHECIDO', 'risk' => 'Inaceitável', 'score' => 150, 'indicator_id' => 9],
    ['description' => 'DINAMARCA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'DJIBUTI', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'REPÚBLICA DOMINICANA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'EGIPTO', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'EL SALVADOR', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'EMIRADOS ÁRABES UNIDOS', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'EQUADOR', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ERITREIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ESLOVÁQUIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ESLOVÉNIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ESPANHA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ESTADOS UNIDOS DA AMÉRICA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'ESTÓNIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ETIÓPIA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'FIDJI', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'FILIPINAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'FINLÂNDIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'FORMOSA (TAIWAN)', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'FRANÇA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'GABÃO', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GÂMBIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GANA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'GEÓRGIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GIBRALTAR', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GRÃ-BRETANHA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],

 ['description' => 'GRANADA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GRÉCIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'GRONELÂNDIA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'GUADALUPE', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'GUAM', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GUATEMALA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GUIANA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GUIANA FRANCESA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'REPÚBLICA DA GUINÉ', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'GUINÉ-BISSAU', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GUINÉ EQUATORIAL', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'GUINÉ-CONACRI', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'HAITI', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'HOLANDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'HONDURAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'HONG KONG', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'HUNGRIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'REPÚBLICA DO IÉMEN', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ILHAS CAIMÃO', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ILHAS CAYMAN', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ILHAS CHRISTMAS', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ILHAS COOK', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ILHAS MALVINAS (FALKLANDS)', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 9],
    ['description' => 'ILHAS MARSHALL', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ILHAS SALOMÃO', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'ILHAS SEYCHELLES', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'INDONÉSIA', 'risk' => 'Alto', 'score' => 3, 'indicator_id' => 9],
    ['description' => 'INDONÉSIA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 9],
    ['description' => 'IRÃO', 'risk' => 'Inaceitável', 'score' => 1000, 'indicator_id' => 9],
];
 



        //inserindo os indicadores
        foreach ($IndicatorType as $value) {
            if (!IndicatorIndicatorType::where('description', $value['description'])->exists()) {
                IndicatorIndicatorType::create($value);
            }
        }

    }

}
