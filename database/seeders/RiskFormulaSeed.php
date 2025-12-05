<?php

namespace Database\Seeders;

use App\Models\Entities\RiskFormula;
use Illuminate\Database\Seeder;

class RiskFormulaSeed extends Seeder
{
    public function run()
    {
        $defaultValues = [
            'identification_capacity'     => 0.05,
            'form_establishment'          => 1,
            'status_residence'            => 0.1,
            'profession'                  => 0.2,
            'pep'                         => 1,
        
            'product_risk'                => 0.1,
            'processesReportedAuthoritie' => 1,
            'santion'                     => 1,
        ];

        $formulas = [
            [
                'name'              => 'Entidades Particulares',
                'category'          => 1,
                'country_residence' => 0.25,
                'nationality'       => 0.15,
                'entity_type'       => 2,
              'channel'                     => 0.05,
            ],
            [
                'name'              => 'Entidades Colectiva',
                'category'          => 0.15,   // CAE
                'form_establishment'=> 0.05,   // forma jurídica
                'country_residence' => 0.15,
                'status_residence'  => 0.1,
                'beneficialOwner'   => 0.2,
                'entity_type'       => 1,
                    'channel'                     => 0.05,
                // remover nationality (não existe na fórmula)
            ]
        ];

        foreach ($formulas as $formula) {
            RiskFormula::create(array_merge($defaultValues, $formula));
        }
    }
}
