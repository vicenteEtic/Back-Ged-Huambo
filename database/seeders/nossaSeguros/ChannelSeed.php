<?php

namespace Database\Seeders\nossaSeguros;

use App\Models\Indicator\IndicatorType as IndicatorIndicatorType;
use App\Models\IndicatorType;
use Illuminate\Database\Seeder;

class ChannelSeed extends Seeder
{
    public function run()
    {
        /**IndicatorType */
        $IndicatorType = [

           

            ['description' => 'AGENCIA ACADEMIA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA AMILCAR CABRAL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA BENGUELA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA CACUACO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA CAMAMA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA DIPANDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA DUNDO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA HUAMBO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA KUITO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA LOBITO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA LUBANGO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA LUBANGO II', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA MARGINAL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA MENONGUE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA MULEMBA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA NAMIBE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA PORTO AMBIOM', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA SIAC BENGUELA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA SIAC CABINDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA SIAC MALANGE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA SIAC ONDJIVA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA SIAC SAURIMO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA SIAC UIGE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'AGENCIA SOYO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'ANA JOSE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'BELAS CC', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'BENGUELA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'BIR - LUANDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'BIR MALANJE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'CABINDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'CABINDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'CACUACO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'CAETANO ANTONIO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'CAMAMA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'CANAIS REMOTOS', 'risk' => 'Médio', 'score' => 2, 'indicator_id' => 11],
            ['description' => 'CARLA FERNANDES', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'CAZENGA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'CUNENE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'DAB - LUANDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'DEP. DE INST. E EMPRESAS PUBLICAS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'DEP. DE VENDAS PORTA A PORTA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'DEP. SERVICOS ESPECIALIZADOS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'DEPARTAMENTO EMPRESAS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'ESTACOES DE SERVICOS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'FATIMA CARVALHO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'GULIVER GOMES', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'HUAMBO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'HUILA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'INACTIVOS', 'risk' => 'Inaceitável', 'score' => 150, 'indicator_id' => 11],
            ['description' => 'INGOMBOTA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'JOAO MONTEIRO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'KIUMA PINTO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'KWANZA SUL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'LEONILDE QUICA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'LOBITO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'LUANDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'LUCIA CANDIDO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'LUENA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'LUNDAS - NORTE E SUL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'MA-AGÊNCIA DE VIAGENS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'MAIANGA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'MALANGE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'MARGINAL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'MEDIACAO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'NUNES GONGA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'PROTOCOLOS', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'RANGEL', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'SBA-LUANDA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'SIAC CAXITO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'SIAC CAZENGA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'SIAC HUAMBO', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'TALATONA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'SIAC ZANGO IV', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'TALATONA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'UNIDADE VENDAS DBA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'VIANA', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],
            ['description' => 'ZAIRE', 'risk' => 'Baixo', 'score' => 1, 'indicator_id' => 11],

        ];

        //inserindo os indicadores
        foreach ($IndicatorType as $value) {
            if (!IndicatorIndicatorType::where('description', $value['description'])->exists()) {
                IndicatorIndicatorType::create($value);
            }
        }
    }
}
