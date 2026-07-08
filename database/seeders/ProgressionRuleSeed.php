<?php

namespace Database\Seeders;

use App\Models\RH\Career\ProgressionRule;
use Illuminate\Database\Seeder;

class ProgressionRuleSeed extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Progressão por Tempo de Serviço',
                'code' => 'PRG-TEMPO',
                'type' => 'progression',
                'min_months_in_category' => 36,
                'min_performance_score' => 60,
                'requires_training' => false,
                'requires_evaluation' => true,
                'salary_increase_percent' => 5,
                'description' => 'Progressão automática após 3 anos na mesma categoria com avaliação satisfatória.',
            ],
            [
                'name' => 'Progressão por Mérito',
                'code' => 'PRG-MERITO',
                'type' => 'progression',
                'min_months_in_category' => 12,
                'min_performance_score' => 85,
                'requires_training' => false,
                'requires_evaluation' => true,
                'salary_increase_percent' => 10,
                'description' => 'Progressão por mérito com avaliação de excelente e mínimo 1 ano na categoria.',
            ],
            [
                'name' => 'Progressão Técnica',
                'code' => 'PRG-TECNICA',
                'type' => 'progression',
                'min_months_in_category' => 24,
                'min_performance_score' => 70,
                'requires_training' => true,
                'requires_evaluation' => true,
                'salary_increase_percent' => 7,
                'description' => 'Progressão técnica com formação obrigatória e avaliação mínima de 70%.',
            ],
            [
                'name' => 'Promoção a Chefe de Secção',
                'code' => 'PROM-SECCAO',
                'type' => 'promotion',
                'min_months_in_category' => 24,
                'min_performance_score' => 75,
                'requires_training' => true,
                'requires_evaluation' => true,
                'from_category' => 'Técnico Superior',
                'to_category' => 'Chefe de Secção',
                'from_level' => 8,
                'to_level' => 7,
                'salary_increase_percent' => 15,
                'description' => 'Promoção a Chefe de Secção para Técnicos Superiores com avaliação boa e formação em liderança.',
            ],
            [
                'name' => 'Promoção a Chefe de Departamento',
                'code' => 'PROM-DEPART',
                'type' => 'promotion',
                'min_months_in_category' => 36,
                'min_performance_score' => 80,
                'requires_training' => true,
                'requires_evaluation' => true,
                'from_category' => 'Chefe de Secção',
                'to_category' => 'Chefe de Departamento',
                'from_level' => 7,
                'to_level' => 6,
                'salary_increase_percent' => 20,
                'description' => 'Promoção a Chefe de Departamento para Chefes de Secção com 3 anos e avaliação de excelente.',
            ],
            [
                'name' => 'Promoção a Diretor Provincial',
                'code' => 'PROM-DIRPROV',
                'type' => 'promotion',
                'min_months_in_category' => 48,
                'min_performance_score' => 85,
                'requires_training' => true,
                'requires_evaluation' => true,
                'from_category' => 'Chefe de Departamento',
                'to_category' => 'Diretor Provincial',
                'from_level' => 6,
                'to_level' => 5,
                'salary_increase_percent' => 25,
                'description' => 'Promoção a Diretor Provincial para Chefes de Departamento com 4 anos de experiência.',
            ],
            [
                'name' => 'Progressão de Técnico Superior para Técnico Médio',
                'code' => 'PRG-SUP-MED',
                'type' => 'progression',
                'min_months_in_category' => 24,
                'min_performance_score' => 65,
                'requires_training' => true,
                'requires_evaluation' => true,
                'from_category' => 'Técnico Médio',
                'to_category' => 'Técnico Superior',
                'from_level' => 9,
                'to_level' => 8,
                'salary_increase_percent' => 10,
                'description' => 'Progressão de Técnico Médio para Técnico Superior com formação complementar.',
            ],
        ];

        foreach ($rules as $rule) {
            ProgressionRule::updateOrCreate(
                ['code' => $rule['code']],
                $rule
            );

            $this->command->info("Regra '{$rule['name']}' criada/actualizada.");
        }
    }
}
