<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('declaration_requests', function (Blueprint $table) {
            // Comuns
            $table->string('nome_completo')->nullable()->after('additional_info');
            $table->string('sexo')->nullable()->after('nome_completo');
            $table->date('data_emissao')->nullable()->after('sexo');
            $table->string('numero_declaracao')->nullable()->after('data_emissao');
            $table->string('assinante_nome')->nullable()->after('numero_declaracao');
            $table->string('assinante_cargo')->nullable()->after('assinante_nome');

            // Quase-comuns
            $table->string('categoria_funcao')->nullable()->after('assinante_cargo');
            $table->string('local_servico')->nullable()->after('categoria_funcao');
            $table->string('vinculo')->nullable()->after('local_servico');
            $table->string('banco')->nullable()->after('vinculo');
            $table->string('tipo_salario')->nullable()->after('banco');
            $table->decimal('salario_numero', 12, 2)->nullable()->after('tipo_salario');
            $table->string('salario_extenso')->nullable()->after('salario_numero');
            $table->decimal('salario_numero_liquido', 12, 2)->nullable()->after('salario_extenso');
            $table->string('salario_extenso_liquido')->nullable()->after('salario_numero_liquido');

            // Específicos
            $table->string('tratamento')->nullable()->after('salario_extenso_liquido');
            $table->string('cargo')->nullable()->after('tratamento');
            $table->string('tempo_servico')->nullable()->after('cargo');
            $table->string('data_admissao')->nullable()->after('tempo_servico');
            $table->string('data_admissao_completa')->nullable()->after('data_admissao');
            $table->string('numero_conta')->nullable()->after('data_admissao_completa');
            $table->string('conta_consignacao')->nullable()->after('numero_conta');
            $table->string('numero_bi')->nullable()->after('conta_consignacao');
            $table->string('numero_agente')->nullable()->after('numero_bi');
            $table->string('telefone')->nullable()->after('numero_agente');
            $table->string('email')->nullable()->after('telefone');
            $table->text('morada')->nullable()->after('email');
            $table->string('balcao_domicilio')->nullable()->after('morada');
            $table->string('finalidade')->nullable()->after('balcao_domicilio');
            $table->string('entidade_empregadora')->nullable()->after('finalidade');
            $table->string('entidade_pagadora')->nullable()->after('entidade_empregadora');
            $table->string('dia_pagamento')->nullable()->after('entidade_pagadora');
            $table->string('embaixada')->nullable()->after('dia_pagamento');
            $table->string('cidade_embaixada')->nullable()->after('embaixada');
            $table->string('local_residencia')->nullable()->after('cidade_embaixada');
            $table->string('tipo_correccao')->nullable()->after('local_residencia');
            $table->string('departamento_emissor')->nullable()->after('tipo_correccao');
        });
    }

    public function down(): void
    {
        Schema::table('declaration_requests', function (Blueprint $table) {
            $columns = [
                'nome_completo', 'sexo', 'data_emissao', 'numero_declaracao', 'assinante_nome', 'assinante_cargo',
                'categoria_funcao', 'local_servico', 'vinculo', 'banco', 'tipo_salario', 'salario_numero',
                'salario_extenso', 'salario_numero_liquido', 'salario_extenso_liquido',
                'tratamento', 'cargo', 'tempo_servico', 'data_admissao', 'data_admissao_completa',
                'numero_conta', 'conta_consignacao', 'numero_bi', 'numero_agente', 'telefone', 'email',
                'morada', 'balcao_domicilio', 'finalidade', 'entidade_empregadora', 'entidade_pagadora',
                'dia_pagamento', 'embaixada', 'cidade_embaixada', 'local_residencia', 'tipo_correccao',
                'departamento_emissor',
            ];

            $table->dropColumn($columns);
        });
    }
};
