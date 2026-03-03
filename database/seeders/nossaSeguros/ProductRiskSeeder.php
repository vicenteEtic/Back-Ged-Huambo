<?php

namespace Database\Seeders\nossaSeguros;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use App\Models\IndicatorType;
use Illuminate\Database\Seeder;

class ProductRiskSeeder extends Seeder
{
    public function run()
    {
        /**IndicatorType */
        $IndicatorType = [

            /**Risco Produtos/ Serviços / Transações 3 */

            ['description' => 'seguro de Propriedade Comercial A.P. - Desporto', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'Acidente TRABALHO/TRAB. C/PROPRIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'Acidente PESSOAIS GRUPO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'Amparo Familiar', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'Amparo Familiar Grupo', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'ASSISTÊNCIA SAÚDE', 'risk' => 'Baixo', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'AUTOMOVEL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'AUTOMOVEL - AKZ', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'AUTOMOVEL CVA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'AUTOMOVEL CVA - AKZ', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'AVARIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'BAI CREDITO PESSOAL DIGITAL', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'CASCO AVARIA DE MÁQUINAS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'CASCO MARÍTIMO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'CASCO AEREO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'CAUÇÃO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'CIVIL PROFISSIONAL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'CONSTRUÇÕES', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'DIVERSOS', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'EMBARCAcidenteOES DE RECREIO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'EMPRESAS CONSTRUÇÃO CIVIL', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'PESSOAS', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'PETROQUÍMICA', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'PRÉMIO FIXO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'PRÉMIO VARIÁVEL', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'PROF.E EXTRA PROFISSIONAL', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'PROTECÇÃO CONTRA ASSALTOS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'QUEBRA DE VIDROS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'ROUBO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SAÚDE BAI PARA TODOS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SAUDE GRUPO', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'SAUDE GRUPO - RESSEGURO 100%', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'SAUDE GRUPO TAILORMADE', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'SAUDE INDIVIDUAL VITAL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SAUDE INDIVIDUAL VITAL LEVE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SAÚDE MWANGOLÉ', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SEG. VIDA CRÉDITO PENSIONISTA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'Seguro BAI Vida', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SEGURO ESCOLAR', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'Seguro Vida Crédito', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'Seguro Vida Crédito (AKZ)', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'Seguro Vida-Fixe', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SPV GRUPO ABERTO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SPV GRUPO FECHADO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SPV INDIVIDUAL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'SPV INDIVIDUAL TEMPORARIO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'VIAGEM', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'VIAGEM E ASSISTÊNCIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'VIAGEM E ASSISTÊNCIA AKZ', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'Vida Risco Grupo', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'Vida Risco Individual', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 7],
            ['description' => 'FUNDO DE PENSÕES BAI', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],
            ['description' => 'FUNDO DE PENSÕES ABERTO - NOSSA Reforma', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 7],

        ];

        //inserindo os indicadores
        foreach ($IndicatorType as $value) {
            IndicatorIndicatorType::create($value);
        }
    }
}
