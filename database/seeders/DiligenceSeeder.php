<?php

namespace Database\Seeders;

use App\Models\Diligence;
use App\Models\Diligence\Diligence as DiligenceDiligence;
use Illuminate\Database\Seeder;

class  DiligenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $RiskType = [

            ['risk' => "Baixo", 'name' => 'Simplificada', 'min' => "1", 'max' => "1.5", 'color' => "#92D050",'reassessmentPeriod' => "3 Anos"],
            ['risk' => "Médio", 'name' => 'Standard', 'min' => "1.6", 'max' => "2.5", 'color' => "#ffc107",'reassessmentPeriod' => "2 Anos"],
            ['risk' => "Alto", 'name' => 'Reforçada', 'min' => "2.6", 'max' => "20", 'color' => "#FFC000",'reassessmentPeriod' => "1 Anos"],
            ['risk' => "Inaceitável", 'name' => 'Cliente Inaceitável', 'min' => "20.01", 'max' => "1000", 'color' => "#ff0000",'reassessmentPeriod' => "1 Anos"],
        ];

        //inserindo os departamentos
        foreach ($RiskType as $value) {
            DiligenceDiligence::create($value);
        }
    }
}
