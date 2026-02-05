<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use App\Models\KYT\KytRule;
use App\Models\KYT\kytrules;

class KytRulesSeeder extends Seeder
{
    public function run(): void
    {
        $products = IndicatorIndicatorType::where('indicator_id', 7)->get();

        $dataProduct = [];

        foreach ($products as $item) {
            $dataProduct[] = $item->description;
        }

        $data = [

            [
                'code' => 'KYT_HIGH_CAPITAL_INCREASE',
                'name' => 'Aumento Irregular',
                'severity' => 'Alto',
                'score' => 30,
                'active' => true,
                'parameters' => [
                    'max_days_between' => 60,
                    'min_increase_rate' => 0.40,
                    'premium_ratio_factor' => 0.6,
                    'high_risk_products' => $dataProduct,
                ],
            ],

            [
                'code' => 'KYT_EARLY_REDEMPTION',
                'name' => 'Resgate Antecipado',
                'severity' => 'Alto',
                'score' => 20,
                'active' => true,
                'parameters' => [
                    'max_days_active' => 365,
                    'loss_days_threshold' => 180,
                    'products' => [
                        'Vida',
                        'Poupança',
                        'Unit Linked',
                        'Capitalização',
                    ],
                ],
            ],

            [
                'code' => 'KYT_HIGH_PREMIUM_LOW_RISK',
                'name' => 'Prêmio Alto, Risco Baixo',
                'severity' => 'Alto',
                'score' => 25,
                'active' => true,
                'parameters' => [
                    'min_ratio' => 0.08,
                    'low_risk_products' => [
                        'Vida Term',
                        'Vida Simples',
                        'Funeral',
                        'Acidentes Pessoais',
                    ],
                ],
            ],

            [
                'code' => 'KYT_MULTIPLE_SHORT_POLICIES',
                'name' => 'Múltiplas Apólices Curtas',
                'severity' => 'Médio',
                'score' => 15,
                'active' => true,
                'parameters' => [
                    'min_policies' => 2,
                    'min_days' => 90,
                    'max_days' => 180,
                    'months_window' => 12,
                    'min_total_capital' => 150000,
                ],
            ],

            [
                'code' => 'KYT_POLICY_CHURNING',
                'name' => 'Rotatividade de Apólices',
                'severity' => 'Médio',
                'score' => 20,
                'active' => true,
                'parameters' => [
                    'max_gap_days' => 60,
                    'capital_variation_limit' => 0.3,
                    'min_cycles' => 2,
                ],
            ],

            [
                'code' => 'KYT_RAPID_POLICY_REPLACEMENT',
                'name' => 'Substituição Rápida de Apólice',
                'severity' => 'Médio',
                'score' => 15,
                'active' => true,
                'parameters' => [
                    'max_gap_days' => 7,
                ],
            ],

        ];

        foreach ($data as $rule) {
            kytrules::updateOrCreate(
                ['code' => $rule['code']],
                $rule
            );
        }
    }
}
