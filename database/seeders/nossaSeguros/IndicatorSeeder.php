<?php

namespace Database\Seeders;

use App\Models\Indicator\Indicator;
use App\Models\IndicatorEvaluationMatrix;
use Illuminate\Database\Seeder;

class IndicatorSeeder extends Seeder
{

    public function run()
    {
        $Indicators = [
            ['name' => "Capacidade de Idenetificação/Verificação"],
            ['name' => "Forma de Estabelecimento de relação de negócio"],
            ['name' => "Forma Juridica da entidade"],
            ['name' => "Residência"],
            ['name' => "Tipo de Actividade Principal"],
            ['name' => "A Entidade é Considerada PPE"],
            ['name' => "Tipo de Seguro"],
            ['name' => "Risco Produtos/ Serviços / Transações 3"],
            ['name' => "Risco Produtos/ Serviços / Transações 4"],
            ['name' => "Tipo de Actividade Principal Colectiva"],
            ['name' => "Canais"],
            ['name' => "CAE"],
        ];

        //inserindo os indicadores
        foreach ($Indicators as $value) {
            Indicator::updateOrCreate(["name" => $value['name']], $value);
        }
    }
}
