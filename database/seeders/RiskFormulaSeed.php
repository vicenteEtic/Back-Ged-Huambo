<?php

namespace Database\Seeders;

use App\Models\Entities\RiskFormula;
use Illuminate\Database\Seeder;

class RiskFormulaSeed extends Seeder
{
    public function run()
    {
        $formulas = [

            // =========================================
            // Entidades Particulares
            // =========================================
            [
                'name' => 'Entidades Particulares',
                'entity_type' => 2,

                // Capacidade de Identificação
                'identification_capacity' => 0.20,

                // Profissão
                'profession' => 0.20,

                // Nacionalidade
                'nationality' => 0.05,

                // País de Residência
                'country_residence' => 0.20,

                // Estado de Residência
                'status_residence' => 0.10,

                // Risco de Produtos
                'product_risk' => 0.10,

                // Processos comunicados
                'processesReportedAuthoritie' => 1,

                // Sanções
                'santion' => 1,

                // PEP
                'pep' => 1,

                // Canal de Distribuição
                'channel' => 0.15,
            ],

            // =========================================
            // Entidades Colectivas (Empresas)
            // =========================================
            [
                'name' => 'Entidades Colectiva',
                'entity_type' => 1,

                // Capacidade de Identificação
                'identification_capacity' => 0.05,

                // Código de Actividade Económica (CAE)
                'category' => 0.15,

                // Forma Jurídica
                // 'form_establishment' => 0.05,

                // País de Residência
                'country_residence' => 0.20,

                // Estado de Residência
                'status_residence' => 0.10,

                // Beneficiário Efetivo
                'beneficialOwner' => 0.20,

                // Risco de Produto
                'product_risk' => 0.10,

                // Processos comunicados
                'processesReportedAuthoritie' => 1,

                // Sanções
                'santion' => 1,

                // Canal de Distribuição
                'channel' => 0.15,
            ]

        ];

        foreach ($formulas as $formula) {
            RiskFormula::create($formula);
        }
    }
}