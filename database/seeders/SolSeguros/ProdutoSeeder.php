<?php

namespace Database\Seeders\SolSeguros;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run()
    {
        /**IndicatorType */
        $IndicatorType = [

            /** Risco Produtos extraídos dinamicamente do arquivo CSV mapeados com base na matriz de riscos fornecida */

            ['description' => 'Automóvel Individual', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 8],
            ['description' => 'Vida Crédito', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 8],
            ['description' => 'Multirriscos Habitacao', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 8], // Mapeado por aproximação estrutural de baixa complexidade patrimonial
            ['description' => 'Vida Risco', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 8],
            ['description' => 'Acidentes Trabalho - Prémio Variável', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 8],
            ['description' => 'Assistencia em Viagem', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 8],
            ['description' => 'Automóvel Frota', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 8],
            ['description' => 'Aviação', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 8], // Mapeado com base em 'CASCO AEREO'
            ['description' => 'Mineiro', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 8], // Alinhado com a classificação de indústrias pesadas/complexas
            ['description' => 'Petrolífero', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 8], // Mapeado com base em 'PETROQUÍMICA'
            ['description' => 'Saúde Grupo', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 8], // Mapeado com base em 'SAUDE GRUPO'
            ['description' => 'Caução', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 8],
            ['description' => 'Responsabilidade Civil Exploração', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 8], // Mapeado com base em 'CIVIL PROFISSIONAL'
            ['description' => 'Construção e Montagens', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 8], // Mapeado com base em 'CONSTRUÇÕES'
            ['description' => 'Acidentes Trabalho - Prémio Fixo', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 8], // Mapeado com base em 'PRÉMIO FIXO'

        ];

        // Inserindo os indicadores
        foreach ($IndicatorType as $value) {
            if (!IndicatorIndicatorType::where('description', $value['description'])->exists()) {
                IndicatorIndicatorType::create($value);
            }
        }
    }
}
