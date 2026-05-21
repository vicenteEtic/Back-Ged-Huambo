<?php

namespace Database\Seeders\SolSeguros;

use App\Models\Indicator\Indicator;
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
            ['name' => "Produto"],
            ['name' => "Agente produtor"],
            ['name' => "Tipo de Actividade Principal Colectiva"],
            ['name' => "Canais"],
            ['name' => "CAE"],
        ];

        // Inserindo ou atualizando os indicadores
        foreach ($Indicators as $value) {
            Indicator::updateOrCreate(["name" => $value['name']], $value);
        }
    }
}