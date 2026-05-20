<?php

namespace Database\Seeders\SolSeguros;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use Illuminate\Database\Seeder;

class CaeSeeder extends Seeder
{
    public function run()
    {
        /**IndicatorType */
        $IndicatorType = [

            /** CAEs extraídos diretamente do arquivo CSV mapeados com base no indicator_id 12 */

            ['description' => 'OUTRAS ACTIVIDADES DE SERVIÇOS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'COMÉRCIO POR GROSSO E A RETALHO; REPARAÇÃO DE VEÍCULOS AUTOMÓVEIS E MOTOCICLOS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'ADMINISTRAÇÃO PÚBLICA E DEFESA; SEGURANÇA SOCIAL OBRIGATÓRIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'TRANSPORTES E ARMAZENAGEM', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'ACTIVIDADES DAS FAMÍLIAS EMPREGADORAS DE PESSOAL DOMÉSTICO E ACTIVIDADES DE PRODUÇÃO DE BENS E SERVIÇOS DAS FAMÍLIAS PARA USO PRÓPRIO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'INDÚSTRIAS EXTRACTIVAS', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 12],
            ['description' => 'CONSTRUÇÃO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 12],
            ['description' => 'ALOJAMENTO, RESTAURAÇÃO E SIMILARES', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'ACTIVIDADES FINANCEIRAS E DE SEGUROS', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 12],
            ['description' => 'INDÚSTRIAS TRANSFORMADORAS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'SAÚDE HUMANA E ACÇÃO SOCIAL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'AGRICULTURA, PRODUÇÃO ANIMAL, CAÇA, SILVICULTURA E PESCA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'EDUCAÇÃO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 12],
            ['description' => 'ACTIVIDADES IMOBILIÁRIAS', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 12],

        ];

        // Inserindo os indicadores caso não existam
        foreach ($IndicatorType as $value) {
            if (!IndicatorIndicatorType::where('description', $value['description'])->where('indicator_id', 12)->exists()) {
                IndicatorIndicatorType::create($value);
            }
        }
    }
}