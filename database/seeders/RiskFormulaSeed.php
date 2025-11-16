<?php

namespace Database\Seeders;

use App\Models\Entities\RiskFormula;
use Illuminate\Database\Seeder;

class RiskFormulaSeed extends Seeder
{
    public function run()
    {
        // Valores padrão reutilizáveis
        $defaultValues = [
            'identification_capacity'     => 0.05,
            'form_establishment'          => 1,
            'status_residence'            => 0.1,
            'profession'                  => 0.2,
            'pep'                         => 1,
            'channel'                     => 0.05,
            'product_risk'                => 0.1,
            'processesReportedAuthoritie' => 1,
            'santion'                     => 1,
            'distributionChannel'         => 1,
        ];

        // Fórmulas específicas
        $formulas = [
            [
                'name'              => 'Entidades Particulares',
                'category'          => 1,
                'country_residence' => 0.25,
                'nationality'       => 0.15,
                'entity_type'       => 1,
                'beneficialOwner'   => 1,
            ],
            [
                'name'              => 'Entidades Colectiva',
                'category'          => 0.05,
                'country_residence' => 0.15,
                'nationality'       => 0.15,
                'entity_type'       => 2,
                'beneficialOwner'   => 0.2,
            ]
        ];

        // Loop reutilizável
        foreach ($formulas as $formula) {
            RiskFormula::create(array_merge($defaultValues, $formula));
        }
    }
}
